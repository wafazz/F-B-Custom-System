<?php

namespace App\Jobs;

use App\Models\CampaignDelivery;
use App\Models\CustomerCart;
use App\Models\ScheduledCampaign;
use App\Services\Push\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAbandonedCartReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(PushService $push): void
    {
        // Admin owns the on/off, copy, and delay via the campaign row.
        $campaign = ScheduledCampaign::activeAbandonedCart();
        if ($campaign === null) {
            return; // feature switched off
        }

        $cart = CustomerCart::query()->where('user_id', $this->userId)->first();
        if (! $cart || $cart->item_count < 1) {
            return; // cart cleared or order placed in the meantime
        }
        if ($cart->notified_at !== null) {
            return; // already reminded for this cart
        }

        // Only fire once the cart has sat untouched for the full window. If the
        // customer kept editing, updated_at is newer and a later-dispatched job
        // will catch the quiet period instead — this naturally debounces.
        $delay = $campaign->delay_minutes ?: (int) config('services.abandoned_cart.delay_minutes', 15);
        if ($cart->updated_at?->greaterThan(now()->subMinutes($delay))) {
            return;
        }

        $report = $push->sendToUser($this->userId, [
            'title' => (string) $campaign->title,
            'body' => (string) $campaign->body,
            'url' => $campaign->url ?: ($cart->branch_id ? "/branches/{$cart->branch_id}/cart" : '/'),
            'tag' => 'abandoned-cart',
        ]);

        if ($report['sent'] > 0) {
            CampaignDelivery::create([
                'scheduled_campaign_id' => $campaign->id,
                'user_id' => $this->userId,
                'sent_at' => now(),
            ]);
        }

        $cart->forceFill(['notified_at' => now()])->save();
    }
}
