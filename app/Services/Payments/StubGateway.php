<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Dev/test stub gateway. Real Billplz adapter slots in via the
 * PaymentGateway contract once W-DEC-? credentials land.
 */
class StubGateway implements PaymentGateway
{
    public function createBill(Order $order): PaymentBill
    {
        $reference = 'STUB-'.Str::upper(Str::random(10));

        return new PaymentBill(
            reference: $reference,
            url: route('orders.simulate-paid', ['order' => $order, 'reference' => $reference]),
            method: 'stub',
        );
    }

    public function verifyWebhook(array $payload, ?string $signature): ?PaymentBillUpdate
    {
        if (! isset($payload['reference'], $payload['status'])) {
            return null;
        }

        $status = PaymentStatus::tryFrom((string) $payload['status']);
        if ($status === null) {
            return null;
        }

        return new PaymentBillUpdate(
            reference: (string) $payload['reference'],
            status: $status,
        );
    }
}
