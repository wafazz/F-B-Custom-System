<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order.ready',
            'title' => 'Your order is ready!',
            'body' => "Order {$this->order->number} is ready for pickup.",
            'url' => route('orders.show', ['order' => $this->order->id], false),
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
        ];
    }
}
