<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrintReceiptRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel("branch.{$this->order->branch_id}.print")];
    }

    public function broadcastAs(): string
    {
        return 'receipt.print';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $order = $this->order->loadMissing(['branch', 'items.modifiers']);

        return [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'order_type' => $order->order_type->value,
                'dine_in_table' => $order->dine_in_table,
                'customer_snapshot' => $order->customer_snapshot,
                'subtotal' => (float) $order->subtotal,
                'total' => (float) $order->total,
                'created_at' => $order->created_at?->toIso8601String(),
                'items' => $order->items->map(fn ($i) => [
                    'name' => $i->product_name,
                    'quantity' => (int) $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                    'line_total' => (float) $i->line_total,
                    'modifiers' => $i->modifiers->map(fn ($m) => [
                        'name' => $m->option_name,
                        'price' => (float) $m->price_delta,
                    ])->values(),
                    'notes' => $i->notes,
                ])->values(),
            ],
            'branch' => [
                'id' => $order->branch->id,
                'name' => $order->branch->name,
                'code' => $order->branch->code,
            ],
        ];
    }
}
