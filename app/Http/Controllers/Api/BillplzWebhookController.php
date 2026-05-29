<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WalletTopup;
use App\Services\Orders\OrderService;
use App\Services\Payments\BillplzGateway;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillplzWebhookController extends Controller
{
    public function __construct(
        protected BillplzGateway $gateway,
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

    /**
     * Mobile in-app browser redirected back from Billplz. The app has no web
     * session, so we can't bounce to the auth-gated orders.show — instead render
     * a public confirmation page that deep-links back into the app.
     */
    public function appReturn(Order $order, Request $request): Response
    {
        $payload = $request->query();
        $update = $this->gateway->verifyWebhook($payload, $request->query('x_signature'));

        if ($update !== null && $update->reference === $order->payment_reference) {
            $this->applyUpdate($order, $update->status);
        }

        $paid = $order->payment_status === PaymentStatus::Paid;
        $deepLink = 'starcoffee://order-return?order='.$order->id;
        $icon = $paid ? '✅' : '⏳';
        $heading = $paid ? 'Payment received' : 'Payment processing';
        $msg = $paid
            ? "Order {$order->number} is confirmed."
            : 'If you completed payment, your order will update shortly. You can cancel safely if you did not pay.';

        $html = <<<HTML
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Star Coffee</title></head>
<body style="margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#F7F3F0;color:#1F1716;display:flex;min-height:100vh;align-items:center;justify-content:center;">
<div style="text-align:center;max-width:340px;padding:32px;">
<div style="font-size:56px;line-height:1;">{$icon}</div>
<h1 style="font-size:20px;margin:16px 0 6px;">{$heading}</h1>
<p style="font-size:14px;color:#6B5048;line-height:1.5;margin:0;">{$msg}</p>
<p style="font-size:13px;color:#92400E;font-weight:600;margin:22px 0 0;">You can close this window and return to the Star Coffee app.</p>
<a href="{$deepLink}" style="display:inline-block;margin-top:18px;background:#402724;color:#F7F3F0;text-decoration:none;padding:13px 26px;border-radius:999px;font-weight:700;font-size:14px;">Return to app</a>
</div>
</body></html>
HTML;

        return response($html);
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
