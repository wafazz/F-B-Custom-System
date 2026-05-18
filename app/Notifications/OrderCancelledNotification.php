<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCancelledNotification extends Notification
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
        $body = "Order {$this->order->number} was cancelled.";
        if ($this->order->cancellation_reason) {
            $body .= ' '.$this->order->cancellation_reason;
        }

        return [
            'type' => 'order.cancelled',
            'title' => 'Order cancelled',
            'body' => $body,
            'url' => route('orders.show', ['order' => $this->order->id], false),
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
        ];
    }
}
