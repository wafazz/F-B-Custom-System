<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected PaymentGateway $payments,
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $payload = new OrderPayload(
            branchId: (int) $request->integer('branch_id'),
            userId: $request->user()?->getKey(),
            orderType: OrderType::from($request->string('order_type')->value()),
            lines: collect($request->input('lines', []))->map(fn (array $line) => new OrderLinePayload(
                productId: (int) $line['product_id'],
                quantity: (int) $line['quantity'],
                modifierOptionIds: array_map('intval', $line['modifier_option_ids'] ?? []),
                notes: $line['notes'] ?? null,
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

        $bill = $this->payments->createBill($order);
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
