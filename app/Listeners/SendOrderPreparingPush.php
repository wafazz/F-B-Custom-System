<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderPreparingPush implements ShouldQueue
{
    public function __construct(private readonly FcmService $fcm) {}

    public function handle(OrderStatusChanged $event): void
    {
        // Fire only on the Pending → Preparing transition. Other transitions
        // (e.g. Ready, Completed) are visible in the queue via Reverb already
        // and don't justify a system-level push.
        if ($event->order->status !== OrderStatus::Preparing) {
            return;
        }
        if ($event->previousStatus === OrderStatus::Preparing->value) {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('scope', DeviceToken::SCOPE_POS)
            ->where('branch_id', $event->order->branch_id)
            ->where('platform', 'android')
            ->pluck('token')
            ->all();

        if ($tokens === []) {
            return;
        }

        $orderNumber = (string) ($event->order->number ?? $event->order->id);

        $this->fcm->sendToTokens($tokens, [
            'title' => 'New order to prepare',
            'body' => "Order #{$orderNumber} is now in Preparing",
            'channel_id' => 'orders',
            'data' => [
                'order_id' => (string) $event->order->id,
                'order_number' => $orderNumber,
                'branch_id' => (string) $event->order->branch_id,
            ],
        ]);
    }
}
