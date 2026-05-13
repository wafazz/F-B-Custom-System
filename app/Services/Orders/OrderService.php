<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Events\OrderQueuedForDineIn;
use App\Events\OrderReadyForDineIn;
use App\Events\OrderStatusChanged;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Enums\PaymentStatus;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Push\PushService;
use App\Services\Referrals\ReferralService;
use App\Services\Vouchers\VoucherService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    public function __construct(
        protected VoucherService $vouchers,
        protected LoyaltyService $loyalty,
        protected PushService $push,
        protected ReferralService $referrals,
        protected WalletService $wallet,
    ) {}

    public function place(OrderPayload $payload): Order
    {
        if (count($payload->lines) === 0) {
            throw new RuntimeException('Cannot place an empty order.');
        }

        return DB::transaction(function () use ($payload) {
            $branch = Branch::query()->lockForUpdate()->findOrFail($payload->branchId);
            if ($branch->status !== 'active' || ! $branch->accepts_orders) {
                throw new RuntimeException('Branch is not accepting orders.');
            }

            $productIds = collect($payload->lines)->pluck('productId')->unique()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->with([
                    'branches' => fn ($q) => $q->where('branches.id', $branch->id),
                    'stocks' => fn ($q) => $q->where('branch_id', $branch->id),
                ])
                ->get()
                ->keyBy('id');

            foreach ($productIds as $id) {
                if (! $products->has($id)) {
                    throw new RuntimeException("Product {$id} not found.");
                }
            }

            $optionIds = collect($payload->lines)->flatMap(fn ($l) => $l->modifierOptionIds)->unique()->values();
            $options = $optionIds->isEmpty()
                ? collect()
                : ModifierOption::query()->whereIn('id', $optionIds)->with('group')->get()->keyBy('id');

            $sstRate = $branch->sst_enabled ? (float) $branch->sst_rate / 100 : 0;
            $serviceRate = $branch->service_charge_enabled ? (float) $branch->service_charge_rate / 100 : 0;
            $subtotal = 0;
            $sstAmount = 0;

            $itemsToInsert = [];
            $stockToDecrement = [];

            foreach ($payload->lines as $line) {
                /** @var Product $product */
                $product = $products->get($line->productId);

                /** @var Branch|null $branchPivot */
                $branchPivot = $product->branches->first();
                $branchPivotRel = $branchPivot?->getRelationValue('pivot');
                if (! $branchPivot || ! $branchPivotRel || ! $branchPivotRel->getAttribute('is_available')) {
                    throw new RuntimeException("{$product->name} is not available at this branch.");
                }

                /** @var BranchStock|null $stock */
                $stock = $product->stocks->first();
                if ($stock && $stock->track_quantity && $stock->quantity < $line->quantity) {
                    throw new RuntimeException("Insufficient stock for {$product->name}.");
                }
                if ($stock && ! $stock->is_available) {
                    throw new RuntimeException("{$product->name} is out of stock.");
                }

                $unitBase = (float) ($branchPivotRel->getAttribute('price_override') ?? $product->base_price);
                $modifiers = [];
                foreach ($line->modifierOptionIds as $optionId) {
                    $option = $options->get($optionId);
                    if (! $option) {
                        throw new RuntimeException("Modifier option {$optionId} not found.");
                    }
                    $unitBase += (float) $option->price_delta;
                    $modifiers[] = $option;
                }

                $lineTotal = $unitBase * $line->quantity;
                $subtotal += $lineTotal;
                if ($product->sst_applicable) {
                    $sstAmount += $lineTotal * $sstRate;
                }

                $itemsToInsert[] = [
                    'product' => $product,
                    'unit_price' => $unitBase,
                    'quantity' => $line->quantity,
                    'line_total' => $lineTotal,
                    'notes' => $line->notes,
                    'modifiers' => $modifiers,
                ];

                if ($stock && $stock->track_quantity) {
                    $stockToDecrement[] = ['stock' => $stock, 'qty' => $line->quantity];
                }
            }

            $sstAmount = round($sstAmount, 2);
            $subtotal = round($subtotal, 2);

            // Voucher discount
            $voucher = null;
            $voucherDiscount = 0.0;
            if (! empty($payload->voucherCode)) {
                $voucher = $this->vouchers->find($payload->voucherCode, $branch->id, $payload->userId);
                $voucherDiscount = $this->vouchers->discountFor($voucher, $subtotal);
            }

            // Loyalty redemption
            $loyaltyDiscount = 0.0;
            if ($payload->loyaltyRedeemPoints > 0 && $payload->userId !== null) {
                $available = $this->loyalty->balance($payload->userId);
                if ($available < $payload->loyaltyRedeemPoints) {
                    throw new RuntimeException('Insufficient loyalty points.');
                }
                $loyaltyDiscount = $this->loyalty->dollarsForPoints($payload->loyaltyRedeemPoints);
            }

            $discountTotal = round(min($subtotal, $voucherDiscount + $loyaltyDiscount), 2);

            // Recompute SST on discounted subtotal proportionally.
            $discountedSubtotal = max(0, $subtotal - $discountTotal);
            if ($discountTotal > 0 && $subtotal > 0) {
                $sstAmount = round($sstAmount * ($discountedSubtotal / $subtotal), 2);
            }
            $serviceChargeAmount = $serviceRate > 0
                ? round($discountedSubtotal * $serviceRate, 2)
                : 0.0;
            $total = round($discountedSubtotal + $sstAmount + $serviceChargeAmount, 2);

            // Wallet payment validates upfront so we don't create a half-paid order.
            if ($payload->paymentMethod === 'wallet') {
                if ($payload->userId === null) {
                    throw new RuntimeException('Wallet payment requires an authenticated user.');
                }
                if ($this->wallet->balance($payload->userId) < $total) {
                    throw new RuntimeException(sprintf(
                        'Wallet balance is insufficient — RM%.2f required.',
                        $total,
                    ));
                }
            }

            $order = Order::create([
                'number' => Order::generateNumber($branch->code),
                'branch_id' => $branch->id,
                'user_id' => $payload->userId,
                'order_type' => $payload->orderType,
                'dine_in_table' => $payload->dineInTable,
                'pickup_at' => $payload->pickupAt,
                'status' => OrderStatus::Pending,
                'subtotal' => $subtotal,
                'sst_amount' => $sstAmount,
                'service_charge_amount' => $serviceChargeAmount,
                'discount_amount' => $discountTotal,
                'total' => $total,
                'notes' => $payload->notes,
                'customer_snapshot' => $payload->customerSnapshot,
                'payment_method' => $payload->paymentMethod === 'wallet' ? 'wallet' : null,
            ]);

            if ($voucher !== null) {
                $this->vouchers->commit($voucher, $order, $voucherDiscount);
            }
            if ($loyaltyDiscount > 0 && $payload->userId !== null) {
                $this->loyalty->redeem($payload->userId, $payload->loyaltyRedeemPoints, $order);
            }

            if ($payload->paymentMethod === 'wallet' && $payload->userId !== null && $total > 0) {
                $this->wallet->debit(
                    $payload->userId,
                    (float) $total,
                    type: 'spend',
                    reference: $order,
                    description: "Order {$order->number}",
                );
                $order->forceFill([
                    'payment_status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                ])->save();
            }

            foreach ($itemsToInsert as $row) {
                /** @var Product $product */
                $product = $row['product'];
                /** @var OrderItem $item */
                $item = $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price' => $row['unit_price'],
                    'quantity' => $row['quantity'],
                    'line_total' => $row['line_total'],
                    'notes' => $row['notes'],
                ]);
                foreach ($row['modifiers'] as $option) {
                    /** @var ModifierOption $option */
                    $group = $option->group;
                    $item->modifiers()->create([
                        'modifier_group_id' => $option->modifier_group_id,
                        'modifier_option_id' => $option->id,
                        'group_name' => $group instanceof ModifierGroup ? $group->name : '—',
                        'option_name' => $option->name,
                        'price_delta' => $option->price_delta,
                    ]);
                }
            }

            foreach ($stockToDecrement as $row) {
                /** @var BranchStock $stock */
                $stock = $row['stock'];
                $stock->applyMovement('sale', -$row['qty'], "order {$order->number}", $order, $payload->userId);
            }

            return $order->fresh(['items.modifiers']);
        });
    }

    public function transition(Order $order, OrderStatus $next): Order
    {
        if (! $order->status->canTransitionTo($next)) {
            throw new RuntimeException("Cannot transition {$order->status->value} → {$next->value}.");
        }

        $previous = $order->status;
        $stamps = match ($next) {
            OrderStatus::Preparing => ['preparing_at' => now()],
            OrderStatus::Ready => ['ready_at' => now()],
            OrderStatus::Completed => ['completed_at' => now()],
            OrderStatus::Cancelled => ['cancelled_at' => now()],
            default => [],
        };

        $order->forceFill(array_merge(['status' => $next], $stamps))->save();

        if ($next === OrderStatus::Cancelled) {
            $this->restoreStock($order);
            // Wallet-paid orders get refunded to wallet on cancellation.
            if ($order->payment_method === 'wallet' && $order->user_id !== null && (float) $order->total > 0) {
                $this->wallet->credit(
                    $order->user_id,
                    (float) $order->total,
                    type: 'refund',
                    reference: $order,
                    description: "Refund for cancelled {$order->number}",
                );
                $order->forceFill(['payment_status' => PaymentStatus::Refunded])->save();
            }
        }

        $fresh = $order->fresh() ?? $order;
        event(new OrderStatusChanged($fresh, $previous->value));

        if ($order->order_type === OrderType::DineIn) {
            if ($next === OrderStatus::Preparing) {
                event(new OrderQueuedForDineIn($fresh));
            } elseif ($next === OrderStatus::Ready) {
                event(new OrderReadyForDineIn($fresh));
            }
        }

        if ($next === OrderStatus::Completed && $fresh->user_id !== null) {
            $this->loyalty->earnFromOrder($fresh);
            $this->loyalty->applyTierUpgrade($fresh->user_id, (float) $fresh->subtotal);
            $this->referrals->maybeAwardForCompletedOrder($fresh);
        }
        if ($next === OrderStatus::Refunded && $fresh->user_id !== null) {
            $this->loyalty->refundFromOrder($fresh);
        }

        // Customer push: pickup-path order ready, or any cancellation.
        if ($fresh->user_id !== null) {
            if ($next === OrderStatus::Ready && $order->order_type === OrderType::Pickup) {
                $this->push->sendToUser($fresh->user_id, [
                    'title' => 'Your order is ready!',
                    'body' => "Order {$fresh->number} is ready for pickup.",
                    'url' => route('orders.show', ['order' => $fresh->id]),
                    'tag' => "order-{$fresh->id}",
                ]);
            }
            if ($next === OrderStatus::Cancelled) {
                $this->push->sendToUser($fresh->user_id, [
                    'title' => 'Order cancelled',
                    'body' => "Order {$fresh->number} was cancelled.".($fresh->cancellation_reason ? " {$fresh->cancellation_reason}" : ''),
                    'url' => route('orders.show', ['order' => $fresh->id]),
                    'tag' => "order-{$fresh->id}",
                ]);
            }
        }

        return $order;
    }

    protected function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id === null) {
                continue;
            }
            $stock = BranchStock::query()
                ->where('branch_id', $order->branch_id)
                ->where('product_id', $item->product_id)
                ->where('track_quantity', true)
                ->first();
            $stock?->applyMovement('adjustment', $item->quantity, "cancel {$order->number}", $order);
        }
    }
}
