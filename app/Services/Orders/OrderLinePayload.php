<?php

namespace App\Services\Orders;

class OrderLinePayload
{
    /**
     * @param  list<int>  $modifierOptionIds
     */
    public function __construct(
        public int $productId,
        public int $quantity,
        public array $modifierOptionIds = [],
        public ?string $notes = null,
    ) {}
}
