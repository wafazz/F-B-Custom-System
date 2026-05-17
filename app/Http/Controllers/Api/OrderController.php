<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Branch;
use App\Models\Order;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use App\Services\Payments\BillplzGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected PaymentGateway $payments,
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $branchId = (int) $request->integer('branch_id');
        $branch = Branch::find($branchId);
        if (! $branch || ! $branch->accepts_orders || ! $branch->isOpenNow()) {
            return response()->json([
                'message' => 'This branch is currently closed. Please try again during operating hours.',
            ], 422);
        }

        $payload = new OrderPayload(
            branchId: $branchId,
            userId: $request->user()?->getKey(),
            orderType: OrderType::from($request->string('order_type')->value()),
            lines: collect($request->input('lines', []))->map(fn (array $line) => new OrderLinePayload(
                productId: isset($line['product_id']) ? (int) $line['product_id'] : null,
                quantity: (int) $line['quantity'],
                modifierOptionIds: array_map('intval', $line['modifier_option_ids'] ?? []),
                notes: $line['notes'] ?? null,
                comboId: isset($line['combo_id']) ? (int) $line['combo_id'] : null,
            ))->all(),
            dineInTable: $request->input('dine_in_table'),
            pickupAt: $request->input('pickup_at'),
            notes: $request->input('notes'),
            customerSnapshot: $request->user() ? [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
            ] : null,
            voucherCode: $request->input('voucher_code'),
            loyaltyRedeemPoints: (int) $request->input('loyalty_redeem_points', 0),
            paymentMethod: $request->input('payment_method') === 'wallet' ? 'wallet' : 'gateway',
            packaging: array_values((array) $request->input('packaging', [])),
            useOwnTumbler: (bool) $request->boolean('use_own_tumbler'),
        );

        try {
            $order = $this->orders->place($payload);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Wallet-paid orders skip the gateway entirely — they're already paid.
        if ($payload->paymentMethod === 'wallet') {
            return response()->json([
                'order' => $this->present($order->fresh(['items.modifiers'])),
                'payment' => [
                    'method' => 'wallet',
                    'url' => route('orders.show', ['order' => $order]),
                ],
            ], 201);
        }

        try {
            $bill = $this->payments->createBill($order);
        } catch (RuntimeException $e) {
            Log::warning('Order placed but payment bill creation failed', [
                'order_id' => $order->id,
                'gateway' => $this->payments::class,
                'error' => $e->getMessage(),
            ]);

            // Roll the order back so stock, vouchers, and loyalty are restored.
            try {
                $this->orders->transition($order->fresh() ?? $order, OrderStatus::Cancelled);
            } catch (Throwable $rollback) {
                Log::error('Failed to cancel order after payment failure', [
                    'order_id' => $order->id,
                    'error' => $rollback->getMessage(),
                ]);
            }

            return response()->json([
                'message' => 'Payment gateway error: '.$e->getMessage(),
            ], 422);
        }

        $order->update([
            'payment_method' => $bill->method,
            'payment_reference' => $bill->reference,
        ]);

        // Track guest-placed orders so they can see their own confirmation page.
        if ($order->user_id === null && $request->hasSession()) {
            $ids = (array) $request->session()->get('placed_order_ids', []);
            $ids[] = $order->id;
            $request->session()->put('placed_order_ids', array_slice(array_unique($ids), -20));
        }

        return response()->json([
            'order' => $this->present($order->fresh(['items.modifiers'])),
            'payment' => [
                'reference' => $bill->reference,
                'url' => $bill->url,
                'method' => $bill->method,
            ],
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['items.modifiers', 'branch']);

        return response()->json(['order' => $this->present($order)]);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->getKey();
        abort_unless($userId !== null, 401);

        $this->cancelStaleUnpaidOrders((int) $userId);

        $orders = Order::query()
            ->where('user_id', $userId)
            ->with(['items:id,order_id,product_name,quantity'])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'orders' => $orders->map(fn (Order $o) => [
                'id' => $o->id,
                'number' => $o->number,
                'status' => $o->status->value,
                'status_label' => $o->status->label(),
                'order_type' => $o->order_type->value,
                'total' => (float) $o->total,
                'payment_status' => $o->payment_status->value,
                'can_pay_again' => $this->canPayAgain($o),
                'items_summary' => $o->items
                    ->map(fn ($i) => "{$i->quantity}× {$i->product_name}")
                    ->join(', '),
                'created_at' => $o->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function payAgain(Order $order, BillplzGateway $gateway): JsonResponse
    {
        abort_unless($this->canPayAgain($order), 422, 'This order is not eligible for payment.');

        try {
            $bill = $gateway->createBill($order->fresh() ?? $order);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $order->forceFill([
            'payment_method' => 'billplz',
            'payment_reference' => $bill->reference,
            'payment_status' => PaymentStatus::Unpaid,
        ])->save();

        return response()->json([
            'order' => $this->present($order->fresh(['items.modifiers'])),
            'payment' => [
                'reference' => $bill->reference,
                'url' => $bill->url,
                'method' => $bill->method,
            ],
        ]);
    }

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
        $snapshot = $order->customer_snapshot ?? [];
        if (($snapshot['source'] ?? null) === 'walk-in') {
            return false;
        }

        return $order->created_at instanceof \Illuminate\Support\Carbon
            && $order->created_at->isToday();
    }

    protected function cancelStaleUnpaidOrders(int $userId): void
    {
        $stale = Order::query()
            ->where('user_id', $userId)
            ->where('status', OrderStatus::Pending->value)
            ->whereIn('payment_status', [PaymentStatus::Unpaid->value, PaymentStatus::Failed->value])
            ->whereDate('created_at', '<', now()->toDateString())
            ->get();

        foreach ($stale as $order) {
            $snapshot = $order->customer_snapshot ?? [];
            if (($snapshot['source'] ?? null) === 'walk-in') {
                continue;
            }
            try {
                $order->forceFill([
                    'cancellation_reason' => 'Auto-cancelled — payment not completed by end of day.',
                ])->save();
                $this->orders->transition($order->fresh() ?? $order, OrderStatus::Cancelled);
            } catch (Throwable $e) {
                Log::warning('API auto-cancel stale unpaid order failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** @return array<string, mixed> */
    protected function present(Order $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $modifiers = [];
            foreach ($item->modifiers as $m) {
                $modifiers[] = [
                    'group_name' => $m->group_name,
                    'option_name' => $m->option_name,
                    'price_delta' => (float) $m->price_delta,
                ];
            }
            $items[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => (float) $item->line_total,
                'notes' => $item->notes,
                'modifiers' => $modifiers,
            ];
        }

        return [
            'id' => $order->id,
            'number' => $order->number,
            'branch_id' => $order->branch_id,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'order_type' => $order->order_type->value,
            'dine_in_table' => $order->dine_in_table,
            'pickup_at' => $order->pickup_at?->toIso8601String(),
            'subtotal' => (float) $order->subtotal,
            'sst_amount' => (float) $order->sst_amount,
            'total' => (float) $order->total,
            'payment_status' => $order->payment_status->value,
            'payment_method' => $order->payment_method,
            'payment_reference' => $order->payment_reference,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'items' => $items,
        ];
    }
}
