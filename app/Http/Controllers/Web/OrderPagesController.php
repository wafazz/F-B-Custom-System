<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\VoucherClaim;
use App\Services\Orders\OrderService;
use App\Services\Payments\BillplzGateway;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrderPagesController extends Controller
{
    public function cart(Branch $branch): Response
    {
        $recommendations = Product::active()
            ->featured()
            ->availableAtBranch($branch->id)
            ->limit(6)
            ->get(['id', 'name', 'slug', 'image', 'base_price', 'tumbler_discount'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'image' => $p->image,
                'price' => (float) $p->priceForBranch($branch->id),
                'tumbler_discount' => (float) $p->tumbler_discount,
            ])
            ->values();

        return Inertia::render('storefront/cart', [
            'branch' => $this->branchSummary($branch),
            'recommendations' => $recommendations,
        ]);
    }

    public function checkout(Branch $branch, Request $request, WalletService $wallet): Response
    {
        $userId = $request->user()?->getKey();

        $vouchers = [];
        if ($userId !== null) {
            $claims = VoucherClaim::query()
                ->where('user_id', $userId)
                ->whereNull('used_at')
                ->with('voucher')
                ->get();

            foreach ($claims as $claim) {
                $v = $claim->voucher;
                if (! $v || $v->status !== 'active') {
                    continue;
                }
                $now = now();
                if ($v->valid_from !== null && $v->valid_from->greaterThan($now)) {
                    continue;
                }
                if ($v->valid_until !== null && $v->valid_until->lessThan($now)) {
                    continue;
                }
                $scope = $v->branch_ids;
                if (is_array($scope) && count($scope) > 0 && ! in_array($branch->id, $scope, true)) {
                    continue;
                }
                $vouchers[] = [
                    'code' => $v->code,
                    'name' => $v->name,
                    'discount_type' => $v->discount_type,
                    'discount_value' => (float) $v->discount_value,
                    'min_subtotal' => (float) $v->min_subtotal,
                    'max_discount' => $v->max_discount !== null ? (float) $v->max_discount : null,
                ];
            }
        }

        $suggestions = Product::active()
            ->featured()
            ->availableAtBranch($branch->id)
            ->limit(6)
            ->get(['id', 'name', 'slug', 'image', 'base_price'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image' => $p->image,
                'price' => (float) $p->priceForBranch($branch->id),
            ])
            ->values();

        return Inertia::render('storefront/checkout', [
            'branch' => $this->branchSummary($branch),
            'wallet_balance' => $userId !== null ? $wallet->balance($userId) : 0,
            'is_authenticated' => $userId !== null,
            'vouchers' => $vouchers,
            'suggestions' => $suggestions,
        ]);
    }

    public function show(Order $order, BillplzGateway $gateway, OrderService $service): Response
    {
        // Self-heal: if the order is awaiting a Billplz payment but the
        // webhook never landed (firewall, wrong X-Signature key, etc.),
        // re-query Billplz directly so the page reflects the real status.
        $this->reconcileOrderPayment($order, $gateway, $service);

        // Auto-cancel any stale unpaid orders for this customer (from prior days).
        if ($order->user_id !== null) {
            $this->cancelStaleUnpaidOrders((int) $order->user_id, $service);
        }

        $order->refresh()->load(['items.modifiers', 'branch']);

        return Inertia::render('storefront/order', [
            'order' => $this->presentOrder($order),
            'reverb' => [
                'channel' => "orders.{$order->id}",
                'event' => 'order.status.changed',
            ],
        ]);
    }

    /**
     * Re-create a Billplz bill for a same-day unpaid/failed order and
     * redirect the customer to the hosted payment page.
     */
    public function payAgain(Order $order, BillplzGateway $gateway): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($this->canPayAgain($order), 422, 'This order is not eligible for payment.');

        try {
            $bill = $gateway->createBill($order->fresh() ?? $order);
        } catch (Throwable $e) {
            Log::warning('Pay again — Billplz createBill failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        $order->forceFill([
            'payment_method' => 'billplz',
            'payment_reference' => $bill->reference,
            'payment_status' => PaymentStatus::Unpaid,
        ])->save();

        return Inertia::location($bill->url);
    }

    /** Dev-only: simulate Billplz callback marking the bill paid + auto-advance to Preparing. */
    public function simulatePaid(Order $order, Request $request, OrderService $service): RedirectResponse
    {
        if ($order->payment_reference !== $request->query('reference')) {
            abort(403, 'Reference mismatch.');
        }
        if ($order->payment_status !== PaymentStatus::Paid) {
            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);
            $service->transition($order->fresh() ?? $order, OrderStatus::Preparing);
        }

        return redirect()->route('orders.show', ['order' => $order]);
    }

    public function index(Request $request, OrderService $service): Response
    {
        $userId = $request->user()?->getKey();
        if ($userId !== null) {
            $this->cancelStaleUnpaidOrders((int) $userId, $service);
        }

        $orders = Order::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(30)
            ->get();

        return Inertia::render('storefront/orders', [
            'orders' => $orders->map(fn (Order $o) => [
                'id' => $o->id,
                'number' => $o->number,
                'status' => $o->status->value,
                'status_label' => $o->status->label(),
                'total' => (float) $o->total,
                'payment_status' => $o->payment_status->value,
                'can_pay_again' => $this->canPayAgain($o),
                'created_at' => $o->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * Whether a still-unpaid online order is eligible for a "Pay Now" retry.
     * Same-day only — stale orders are auto-cancelled instead.
     */
    protected function canPayAgain(Order $order): bool
    {
        if ($order->user_id === null) {
            return false;
        }
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }
        if (! in_array($order->payment_status, [PaymentStatus::Unpaid, PaymentStatus::Failed], true)) {
            return false;
        }
        if (! $this->isOnlineOrder($order)) {
            return false;
        }

        return $order->created_at instanceof \Illuminate\Support\Carbon
            && $order->created_at->isToday();
    }

    /** Walk-in POS orders aren't paid via gateway and shouldn't get the retry button. */
    protected function isOnlineOrder(Order $order): bool
    {
        $snapshot = $order->customer_snapshot ?? [];

        return ($snapshot['source'] ?? null) !== 'walk-in';
    }

    /**
     * Cancel pending+unpaid online orders placed before today.
     * Defensive — if anything throws, log it and move on so the page still renders.
     */
    protected function cancelStaleUnpaidOrders(int $userId, OrderService $service): void
    {
        $stale = Order::query()
            ->where('user_id', $userId)
            ->where('status', OrderStatus::Pending->value)
            ->whereIn('payment_status', [PaymentStatus::Unpaid->value, PaymentStatus::Failed->value])
            ->whereDate('created_at', '<', now()->toDateString())
            ->get();

        foreach ($stale as $order) {
            if (! $this->isOnlineOrder($order)) {
                continue;
            }
            try {
                $order->forceFill([
                    'cancellation_reason' => 'Auto-cancelled — payment not completed by end of day.',
                ])->save();
                $service->transition($order->fresh() ?? $order, OrderStatus::Cancelled);
            } catch (Throwable $e) {
                Log::warning('Auto-cancel stale unpaid order failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Re-query Billplz for an order whose payment is still Unpaid and apply
     * paid status + auto-advance to Preparing. Idempotent — early-returns
     * for wallet-paid orders, stub-method orders, or orders that already
     * left Unpaid status. Failures are logged but never thrown.
     */
    protected function reconcileOrderPayment(Order $order, BillplzGateway $gateway, OrderService $service): void
    {
        if ($order->payment_status !== PaymentStatus::Unpaid) {
            return;
        }
        if ($order->payment_method !== 'billplz' || ! $order->payment_reference) {
            return;
        }

        try {
            $bill = $gateway->fetchBill((string) $order->payment_reference);
            $paid = (bool) ($bill['paid'] ?? false);
            $state = (string) ($bill['state'] ?? '');

            if ($paid) {
                $order->forceFill([
                    'payment_status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                ])->save();

                if ($order->status === OrderStatus::Pending) {
                    $service->transition($order->fresh() ?? $order, OrderStatus::Preparing);
                }
            } elseif ($state === 'deleted') {
                $order->forceFill(['payment_status' => PaymentStatus::Failed])->save();
            }
        } catch (Throwable $e) {
            Log::warning('Order payment reconcile failed', [
                'order_id' => $order->id,
                'reference' => $order->payment_reference,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @return array<string, mixed> */
    protected function branchSummary(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'code' => $branch->code,
            'name' => $branch->name,
            'sst_rate' => (float) $branch->sst_rate,
            'sst_enabled' => (bool) $branch->sst_enabled,
            'service_charge_rate' => (float) $branch->service_charge_rate,
            'service_charge_enabled' => (bool) $branch->service_charge_enabled,
            'accepts_orders' => $branch->accepts_orders,
            'is_open_now' => $branch->isOpenNow(),
        ];
    }

    /** @return array<string, mixed> */
    protected function presentOrder(Order $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $modifiers = [];
            foreach ($item->modifiers as $m) {
                $modifiers[] = [
                    'group_name' => $m->group_name,
                    'option_name' => $m->option_name,
                ];
            }
            $items[] = [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => (float) $item->line_total,
                'modifiers' => $modifiers,
            ];
        }

        $branch = $order->branch;

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'order_type' => $order->order_type->value,
            'dine_in_table' => $order->dine_in_table,
            'pickup_at' => $order->pickup_at?->toIso8601String(),
            'branch' => [
                'id' => $order->branch_id,
                'code' => $branch?->code,
                'name' => $branch?->name,
            ],
            'subtotal' => (float) $order->subtotal,
            'sst_amount' => (float) $order->sst_amount,
            'total' => (float) $order->total,
            'payment_status' => $order->payment_status->value,
            'payment_method' => $order->payment_method,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'can_pay_again' => $this->canPayAgain($order),
            'items' => $items,
        ];
    }
}
