<?php

namespace App\Services\Orders;

use App\Enums\OrderType;

class OrderPayload
{
    /**
     * @param  list<OrderLinePayload>  $lines
     */
    public function __construct(
        public int $branchId,
        public ?int $userId,
        public OrderType $orderType,
        public array $lines,
        public ?string $dineInTable = null,
        public ?string $pickupAt = null,
        public ?string $notes = null,
        /** @var array<string, mixed>|null */
        public ?array $customerSnapshot = null,
        public ?string $voucherCode = null,
        public int $loyaltyRedeemPoints = 0,
        public string $paymentMethod = 'gateway', // 'gateway' (Billplz/Stub) or 'wallet'
        public ?int $shiftId = null,
    ) {}
}
