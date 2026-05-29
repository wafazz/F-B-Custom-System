<?php

namespace App\Jobs;

use App\Models\DeviceToken;
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

        $isUsual = $campaign->audience === 'usual';

        $query->chunkById(500, function ($users) use ($campaign, $push, $isUsual) {
            // Usual-order reminders need each customer's most-bought item; pull
            // them for the whole chunk in one query.
            $usualMap = $isUsual ? $this->usualProductsFor($users->pluck('id')->map(fn ($id) => (int) $id)->all()) : [];

            $delivered = [];
            foreach ($users as $user) {
                $usual = $usualMap[(int) $user->getKey()] ?? null;
                // No purchase history → nothing to remind them of; skip.
                if ($isUsual && $usual === null) {
                    continue;
                }
                $report = $push->sendToUser((int) $user->getKey(), [
                    'title' => $campaign->renderMessage((string) $campaign->title, $user, null, $usual),
                    'body' => $campaign->renderMessage((string) $campaign->body, $user, null, $usual),
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

        if ($campaign->audience === 'usual') {
            $ids = $this->usualReminderUserIds($campaign);

            return $ids === [] ? null : $base->whereIn('id', $ids);
        }

        if ($campaign->audience === 'voucher_holders') {
            $ids = $this->voucherHolderUserIds($campaign);

            return $ids === [] ? null : $base->whereIn('id', $ids);
        }

        // 'all' — every reachable customer: web-push subscribers OR mobile-app
        // device-token holders (app-only users have no PushSubscription, so
        // selecting on subscriptions alone would silently skip them).
        $webIds = PushSubscription::query()->whereNotNull('user_id')->distinct()->pluck('user_id');
        $mobileIds = DeviceToken::query()
            ->where('scope', DeviceToken::SCOPE_CUSTOMER)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        $subscriberIds = $webIds->merge($mobileIds)->unique();

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
     * "Come back for your usual" reminder. Targets customers whose last order
     * was N+ days ago (lapsing buyers), throttled by inactivity_cooldown_days
     * so the same person isn't nudged every daily scan. Anyone with no usual
     * (no completed orders) is dropped later in the send loop.
     *
     * @return list<int>
     */
    private function usualReminderUserIds(ScheduledCampaign $campaign): array
    {
        $days = (int) $campaign->inactivity_days;
        if ($days < 1) {
            return [];
        }
        $target = now()->subDays($days)->toDateString();

        $ids = DB::table('orders')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('DATE(MAX(created_at)) <= ?', [$target])
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids === []) {
            return [];
        }

        return array_values(array_diff($ids, $this->recentlyNudged($campaign)));
    }

    /**
     * Each customer's most-bought item across their completed orders, keyed by
     * user_id. One grouped query for the whole id set; PHP keeps the top row
     * per customer (rows arrive ordered by user then quantity desc). Users with
     * no completed orders are simply absent from the map.
     *
     * @param  list<int>  $userIds
     * @return array<int, string>
     */
    private function usualProductsFor(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.user_id', $userIds)
            ->where('orders.status', 'completed')
            ->whereNotNull('order_items.product_name')
            ->groupBy('orders.user_id', 'order_items.product_name')
            ->orderBy('orders.user_id')
            ->orderByDesc('qty')
            ->select('orders.user_id', 'order_items.product_name', DB::raw('SUM(order_items.quantity) as qty'))
            ->get();

        $usual = [];
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            if (! isset($usual[$uid])) {
                $usual[$uid] = (string) $row->product_name; // first = highest qty
            }
        }

        return $usual;
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
     * Customers who currently hold the campaign's voucher — i.e. have claimed
     * it and not yet used it. Lets the admin push a notification to exactly the
     * holders of one specific voucher. No voucher set → no audience.
     *
     * @return list<int>
     */
    private function voucherHolderUserIds(ScheduledCampaign $campaign): array
    {
        if ($campaign->voucher_id === null) {
            return [];
        }

        return VoucherClaim::query()
            ->where('voucher_id', $campaign->voucher_id)
            ->whereNull('used_at')
            ->whereNotNull('user_id')
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
