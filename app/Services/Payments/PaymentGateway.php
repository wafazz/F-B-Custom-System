<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    public function createBill(Order $order, ?string $redirectUrl = null): PaymentBill;

    public function verifyWebhook(array $payload, ?string $signature): ?PaymentBillUpdate;
}
