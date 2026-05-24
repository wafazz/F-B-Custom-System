<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Models\ScheduledCampaign;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\Push\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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

        $query = $this->audienceQuery($campaign);
        if ($query === null) {
            return;
        }

        $query->chunkById(500, function ($users) use ($campaign, $push) {
            $delivered = [];
            foreach ($users as $user) {
                $report = $push->sendToUser((int) $user->getKey(), [
                    'title' => $this->fill((string) $campaign->title, $user),
                    'body' => $this->fill((string) $campaign->body, $user),
                    'url' => $campaign->url ?: '/',
                    'tag' => 'campaign-'.$campaign->id,
                ]);
                if ($report['sent'] > 0) {
                    $delivered[] = [
                        'scheduled_campaign_id' => $campaign->id,
                        'user_id' => $user->getKey(),
                        'sent_at' => now(),
                    ];
                }
            }
            if ($delivered !== []) {
                \App\Models\CampaignDelivery::insert($delivered);
            }
        });
    }

    /**
     * Build the customer query for this campaign, or null when there's no one
     * to target. Always excludes staff/banned. PushService no-ops anyone
     * without a subscription, so non-subscribers are harmlessly skipped.
     */
    private function audienceQuery(ScheduledCampaign $campaign): ?Builder
    {
        $base = User::query()->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::STAFF_ROLES));

        if ($campaign->audience === 'inactive') {
            $ids = $this->inactiveUserIds($campaign);

            return $ids === [] ? null : $base->whereIn('id', $ids);
        }

        // 'all' — every opted-in (subscribed) customer.
        $subscriberIds = PushSubscription::query()->whereNotNull('user_id')->distinct()->pluck('user_id');

        return $subscriberIds->isEmpty() ? null : $base->whereIn('id', $subscriberIds);
    }

    /**
     * Customers who crossed the inactivity threshold *today* — i.e. their last
     * activity was exactly N days ago. Firing on the crossing day (not "N+
     * days") makes a 7/14/30-day ladder send once each, with no daily spam.
     *
     * @return list<int>
     */
    private function inactiveUserIds(ScheduledCampaign $campaign): array
    {
        $days = (int) $campaign->inactivity_days;
        if ($days < 1) {
            return [];
        }
        $target = now()->subDays($days)->toDateString();

        if ($campaign->inactivity_signal === 'last_seen') {
            // user_presence holds only the latest seen-at, so this IS "no app
            // activity since that day".
            return UserPresence::query()
                ->whereDate('last_seen_at', $target)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        // last_order — most recent order was on the target day (none since).
        return DB::table('orders')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('DATE(MAX(created_at)) = ?', [$target])
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function fill(string $text, User $user): string
    {
        $first = trim((string) explode(' ', (string) $user->name)[0]);

        return str_replace('{name}', $first, $text);
    }
}
