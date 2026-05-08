<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;

class PaymentBillUpdate
{
    public function __construct(
        public string $reference,
        public PaymentStatus $status,
    ) {}
}
