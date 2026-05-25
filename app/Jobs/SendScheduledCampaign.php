<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Models\ScheduledCampaign;
use App\Models\User;
use App\Models\UserPresence;
use App\Models\VoucherClaim;
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
                    'title' => $campaign->renderMessage((string) $campaign->title, $user),
                    'body' => $campaign->renderMessage((string) $campaign->body, $user),
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

        if ($campaign->audience === 'voucher_expiry') {
            $ids = $this->voucherExpiryUserIds($campaign);

            return $ids === [] ? null : $base->whereIn('id', $ids);
        }

        if ($campaign->audience === 'birthday') {
            $ids = $this->birthdayUserIds($campaign);

            return $ids === [] ? null : $base->whereIn('id', $ids);
        }

        // 'all' — every opted-in (subscribed) customer.
        $subscriberIds = PushSubscription::query()->whereNotNull('user_id')->distinct()->pluck('user_id');

        return $subscriberIds->isEmpty() ? null : $base->whereIn('id', $subscriberIds);
    }

    /**
     * Customers who hit the inactivity threshold. Two modes (admin choice):
     *  - drip (inactivity_repeat = false): last activity was *exactly* N days
     *    ago, so a 7/14/30-day ladder sends once each with no daily spam.
     *  - repeat (inactivity_repeat = true): last activity was N *or more* days
     *    ago, re-nudged every scan but throttled by inactivity_cooldown_days
     *    (skip anyone already sent this campaign within that window).
     *
     * @return list<int>
     */
    private function inactiveUserIds(ScheduledCampaign $campaign): array
    {
        $days = (int) $campaign->inactivity_days;
        if ($days < 1) {
            return [];
        }
        $repeat = (bool) $campaign->inactivity_repeat;
        $target = now()->subDays($days)->toDateString();

        if ($campaign->inactivity_signal === 'last_seen') {
            // user_presence holds only the latest seen-at, so this IS "no app
            // activity since that day".
            $ids = UserPresence::query()
                ->when($repeat,
                    fn ($q) => $q->whereDate('last_seen_at', '<=', $target),
                    fn ($q) => $q->whereDate('last_seen_at', $target))
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            // last_order — most recent order on (drip) / on-or-before (repeat)
            // the target day, with nothing since.
            $ids = DB::table('orders')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->havingRaw('DATE(MAX(created_at)) '.($repeat ? '<=' : '=').' ?', [$target])
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($repeat && $ids !== []) {
            $ids = array_values(array_diff($ids, $this->recentlyNudged($campaign)));
        }

        return $ids;
    }

    /**
     * Users already sent this campaign within its re-send cooldown — excluded
     * so repeat reminders don't fire every daily scan.
     *
     * @return list<int>
     */
    private function recentlyNudged(ScheduledCampaign $campaign): array
    {
        $cooldown = max(1, (int) $campaign->inactivity_cooldown_days);

        return \App\Models\CampaignDelivery::query()
            ->where('scheduled_campaign_id', $campaign->id)
            ->where('sent_at', '>=', now()->subDays($cooldown))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Customers holding an unused voucher that expires in exactly N days —
     * fires the reminder once, N days before it lapses (no daily spam).
     *
     * @return list<int>
     */
    private function voucherExpiryUserIds(ScheduledCampaign $campaign): array
    {
        $days = (int) $campaign->inactivity_days;
        if ($days < 0) {
            return [];
        }
        $target = now()->addDays($days)->toDateString();

        return VoucherClaim::query()
            ->whereNull('used_at')
            ->whereNotNull('user_id')
            ->whereHas('voucher', fn ($q) => $q
                ->where('status', 'active')
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', $target))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Customers whose birthday falls in the current month, greeted once per
     * birthday month (a same-month delivery means they've already had it). When
     * the campaign carries a voucher, anyone who's already claimed it is dropped
     * — they've got it, so the reminders stop.
     *
     * @return list<int>
     */
    private function birthdayUserIds(ScheduledCampaign $campaign): array
    {
        $now = now();

        $ids = User::query()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $now->month)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($ids === []) {
            return [];
        }

        // Already greeted this birthday month — send once, no daily spam.
        $greeted = \App\Models\CampaignDelivery::query()
            ->where('scheduled_campaign_id', $campaign->id)
            ->whereYear('sent_at', $now->year)
            ->whereMonth('sent_at', $now->month)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $ids = array_values(array_diff($ids, $greeted));

        // A claimed voucher means they already took it — stop nudging them.
        if ($campaign->voucher_id !== null && $ids !== []) {
            $claimed = VoucherClaim::query()
                ->where('voucher_id', $campaign->voucher_id)
                ->whereIn('user_id', $ids)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $ids = array_values(array_diff($ids, $claimed));
        }

        return $ids;
    }
}
