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
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
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
            $total = round($subtotal + $sstAmount, 2);

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
                'discount_amount' => 0,
                'total' => $total,
                'notes' => $payload->notes,
                'customer_snapshot' => $payload->customerSnapshot,
            ]);

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
