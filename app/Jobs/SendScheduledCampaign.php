<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Models\ScheduledCampaign;
use App\Models\User;
use App\Services\Push\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendScheduledCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const STAFF_ROLES = [
        'super_admin', 'hq_admin', 'ops_manager', 'mkt_manager',
        'branch_manager', 'cashier', 'barista',
    ];

    public function __construct(public int $campaignId) {}

    public function handle(PushService $push): void
    {
        $campaign = ScheduledCampaign::find($this->campaignId);
        if (! $campaign) {
            return;
        }

        // Only customers (not staff/banned) who have at least one push
        // subscription can receive this — anyone else is silently skipped.
        $subscriberIds = PushSubscription::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        if ($subscriberIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $subscriberIds)
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::STAFF_ROLES))
            ->chunkById(500, function ($users) use ($campaign, $push) {
                foreach ($users as $user) {
                    $push->sendToUser((int) $user->getKey(), [
                        'title' => $this->fill($campaign->title, $user),
                        'body' => $this->fill($campaign->body, $user),
                        'url' => $campaign->url ?: '/',
                        'tag' => 'campaign-'.$campaign->id,
                    ]);
                }
            });
    }

    private function fill(string $text, User $user): string
    {
        $first = trim((string) explode(' ', (string) $user->name)[0]);

        return str_replace('{name}', $first, $text);
    }
}
