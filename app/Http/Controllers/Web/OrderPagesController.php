<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Services\Orders\OrderService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderPagesController extends Controller
{
    public function cart(Branch $branch): Response
    {
        return Inertia::render('storefront/cart', [
            'branch' => $this->branchSummary($branch),
        ]);
    }

    public function checkout(Branch $branch, Request $request, WalletService $wallet): Response
    {
        $userId = $request->user()?->getKey();

        return Inertia::render('storefront/checkout', [
            'branch' => $this->branchSummary($branch),
            'wallet_balance' => $userId !== null ? $wallet->balance($userId) : 0,
            'is_authenticated' => $userId !== null,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['items.modifiers', 'branch']);

        return Inertia::render('storefront/order', [
            'order' => $this->presentOrder($order),
            'reverb' => [
                'channel' => "orders.{$order->id}",
                'event' => 'order.status.changed',
            ],
        ]);
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

    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->where('user_id', $request->user()?->getKey())
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
                'created_at' => $o->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function branchSummary(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'code' => $branch->code,
            'name' => $branch->name,
            'sst_rate' => (float) $branch->sst_rate,
            'sst_enabled' => $branch->sst_enabled,
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
            'items' => $items,
        ];
    }
}
