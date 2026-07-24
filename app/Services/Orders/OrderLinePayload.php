<?php

namespace App\Services\Orders;

class OrderLinePayload
{
    /**
     * @param  list<int>  $modifierOptionIds
     */
    public function __construct(
        public ?int $productId,
        public int $quantity,
        public array $modifierOptionIds = [],
        public ?string $notes = null,
        public ?int $comboId = null,
        /** Buy-X-Get-Y picker output: voucher code + 'paid'|'free' role. */
        public ?string $voucherCode = null,
        public ?string $voucherRole = null,
        /** Added from the pre-checkout upsell — priced at the HQ upsell price. */
        public bool $isUpsell = false,
    ) {}
}
