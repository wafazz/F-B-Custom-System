<?php

namespace App\Services\Payments;

class PaymentBill
{
    public function __construct(
        public string $reference,
        public string $url,
        public string $method,
    ) {}
}
