<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BranchStockChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $branchId,
        public int $productId,
        public bool $isAvailable,
        public int $quantity,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel("branch.{$this->branchId}.stock")];
    }

    public function broadcastAs(): string
    {
        return 'stock.changed';
    }

    /** @return array<string, int|bool> */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->productId,
            'is_available' => $this->isAvailable,
            'quantity' => $this->quantity,
        ];
    }
}
