<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Loyalty\LoyaltyService;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    /**
     * Public, signed-URL receipt view. Anyone with the link (typically the
     * customer who placed the order, no login required) can see a digital
     * version of their receipt. Designed to be the same URL we will later
     * encode in a QR on the thermal print.
     */
    public function show(Order $order, LoyaltyService $loyalty): Response
    {
        $order->loadMissing(['branch', 'items.modifiers', 'user', 'redemptions.voucher:id,code,name']);
        $branch = $order->branch;

        $pointsEarned = $order->user_id
            ? (int) floor((float) $order->subtotal * $loyalty->multiplierFor((int) $order->user_id))
            : 0;

        return Inertia::render('storefront/receipt-public', [
            'order' => [
                'number' => $order->number,
                'order_type' => $order->order_type->value,
                'dine_in_table' => $order->dine_in_table,
                'created_at' => $order->created_at?->toIso8601String(),
                'paid_at' => $order->paid_at?->toIso8601String(),
                'payment_status' => $order->payment_status->value,
                'payment_method' => $order->payment_method,
                'payment_reference' => $order->payment_reference,
                'subtotal' => (float) $order->subtotal,
                'sst_amount' => (float) $order->sst_amount,
                'service_charge_amount' => (float) ($order->service_charge_amount ?? 0),
                'discount_amount' => (float) ($order->discount_amount ?? 0),
                'tumbler_discount_amount' => (float) ($order->tumbler_discount_amount ?? 0),
                'total' => (float) $order->total,
                'customer_name' => $order->user?->name
                    ?? ($order->customer_snapshot['name'] ?? null),
                'points_earned' => $pointsEarned,
                'vouchers' => $order->redemptions->map(fn ($r) => [
                    'code' => $r->voucher?->code,
                    'name' => $r->voucher?->name,
                    'discount_amount' => (float) $r->discount_amount,
                ])->values()->all(),
                'items' => $order->items->map(fn ($i) => [
                    'name' => $i->product_name,
                    'quantity' => (int) $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                    'line_total' => (float) $i->line_total,
                    'voucher_code' => $i->voucher_code,
                    'voucher_role' => $i->voucher_role ?? null,
                    'modifiers' => $i->modifiers
                        ->map(fn ($m) => ['option_name' => $m->option_name])
                        ->values()
                        ->all(),
                ])->values()->all(),
            ],
            'branch' => [
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'receipt_header' => $branch->receipt_header,
                'receipt_footer' => $branch->receipt_footer,
                'sst_rate' => (float) $branch->sst_rate,
                'service_charge_rate' => (float) $branch->service_charge_rate,
            ],
        ]);
    }
}
