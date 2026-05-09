<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WalletTopup;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentGateway;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillplzWebhookController extends Controller
{
    public function __construct(
        protected PaymentGateway $gateway,
        protected OrderService $orders,
        protected WalletService $wallet,
    ) {}

    /** Server-to-server callback. Billplz posts form-encoded data here. */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $update = $this->gateway->verifyWebhook($payload, $request->header('x-signature'));

        if ($update === null) {
            return response()->json(['ok' => false, 'reason' => 'verification_failed'], 422);
        }

        // Try wallet top-up first, then order — both can use Billplz bills.
        $topup = WalletTopup::firstWhere('billplz_reference', $update->reference);
        if ($topup) {
            $this->applyTopupUpdate($topup, $update->status);

            return response()->json(['ok' => true, 'kind' => 'topup']);
        }

        $order = Order::firstWhere('payment_reference', $update->reference);
        if (! $order) {
            return response()->json(['ok' => false, 'reason' => 'reference_not_found'], 404);
        }

        $this->applyUpdate($order, $update->status);

        return response()->json(['ok' => true, 'kind' => 'order']);
    }

    /** Customer browser redirected back from Billplz hosted page. */
    public function return(Order $order, Request $request): RedirectResponse
    {
        $payload = $request->query();
        $update = $this->gateway->verifyWebhook($payload, $request->query('x_signature'));

        if ($update !== null && $update->reference === $order->payment_reference) {
            $this->applyUpdate($order, $update->status);
        }

        return redirect()->route('orders.show', ['order' => $order]);
    }

    protected function applyUpdate(Order $order, PaymentStatus $status): void
    {
        if ($order->payment_status === $status) {
            return; // idempotent — webhooks may arrive twice
        }

        try {
            $order->forceFill([
                'payment_status' => $status,
                'paid_at' => $status === PaymentStatus::Paid ? now() : $order->paid_at,
            ])->save();

            // Auto-advance Pending → Preparing once payment lands.
            if ($status === PaymentStatus::Paid && $order->status === OrderStatus::Pending) {
                $this->orders->transition($order->fresh() ?? $order, OrderStatus::Preparing);
            }
        } catch (Throwable $e) {
            Log::warning('Billplz status apply failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function applyTopupUpdate(WalletTopup $topup, PaymentStatus $status): void
    {
        if ($topup->status === 'paid' || $topup->status === 'failed') {
            return; // already settled
        }

        try {
            if ($status === PaymentStatus::Paid) {
                $this->wallet->applyTopupPaid($topup);
            } elseif ($status === PaymentStatus::Failed) {
                $topup->forceFill(['status' => 'failed'])->save();
            }
        } catch (Throwable $e) {
            Log::warning('Billplz topup apply failed', [
                'topup_id' => $topup->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
