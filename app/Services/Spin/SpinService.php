<?php

namespace App\Services\Spin;

use App\Models\PointTransaction;
use App\Models\SpinAttempt;
use App\Models\SpinWheelSegment;
use App\Models\User;
use App\Models\VoucherClaim;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Vouchers\VoucherService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SpinService
{
    /**
     * QA accounts that may spin unlimited times. Only their first spin each
     * calendar day credits a reward; every spin after that is reward-free.
     */
    protected const DEVELOPER_EMAILS = ['fanarezqi@gmail.com'];

    public function __construct(
        protected LoyaltyService $loyalty,
        protected VoucherService $vouchers,
    ) {
    }

    /**
     * Spin the wheel for $userId. Throws if already spun today. Picks a
     * segment via weighted random, credits the prize, returns the attempt
     * (with segment loaded) so the controller can surface the result.
     */
    public function spin(int $userId): SpinAttempt
    {
        return DB::transaction(function () use ($userId) {
            $isDeveloper = $this->isDeveloper($userId);

            // Cooldown: one spin per calendar day. Developer accounts may spin
            // unlimited times for QA.
            $todayAttempt = SpinAttempt::query()
                ->where('user_id', $userId)
                ->whereDate('spun_at', today())
                ->lockForUpdate()
                ->first();
            if ($todayAttempt !== null && ! $isDeveloper) {
                throw new RuntimeException('You have already spun today. Come back tomorrow!');
            }

            // Only the first spin of the day pays out — developer replays land
            // on a segment but credit nothing.
            $creditsReward = $todayAttempt === null;

            $segment = $this->pickWeightedSegment();
            $awardedPoints = 0;
            $voucherClaimId = null;

            if ($creditsReward && $segment->prize_type === 'points' && $segment->prize_points !== null && $segment->prize_points > 0) {
                $balance = $this->loyalty->balance($userId);
                PointTransaction::create([
                    'user_id' => $userId,
                    'type' => 'adjustment',
                    'points' => $segment->prize_points,
                    'balance_after' => $balance + $segment->prize_points,
                    'reason' => "spin wheel: {$segment->label}",
                ]);
                $awardedPoints = $segment->prize_points;
            }

            if ($creditsReward && $segment->prize_type === 'voucher' && $segment->voucher_id !== null && $segment->voucher !== null) {
                // Sidestep the per-user uniqueness on voucher_claims by
                // checking — if already claimed, fall through with no
                // prize so the spin doesn't fail entirely.
                $alreadyClaimed = VoucherClaim::query()
                    ->where('voucher_id', $segment->voucher_id)
                    ->where('user_id', $userId)
                    ->exists();
                if (! $alreadyClaimed) {
                    try {
                        $claim = $this->vouchers->claim($segment->voucher, $userId);
                        $voucherClaimId = $claim->id;
                    } catch (\Throwable) {
                        // Stock/eligibility caps trip — let the spin still be
                        // recorded so the daily cooldown applies.
                    }
                }
            }

            return SpinAttempt::create([
                'user_id' => $userId,
                'segment_id' => $segment->id,
                'awarded_points' => $awardedPoints,
                'voucher_claim_id' => $voucherClaimId,
                'spun_at' => now(),
            ])->load('segment');
        });
    }

    public function pickWeightedSegment(): SpinWheelSegment
    {
        $segments = SpinWheelSegment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        if ($segments->isEmpty()) {
            throw new RuntimeException('The wheel has no segments configured.');
        }

        $total = (int) $segments->sum('weight');
        if ($total <= 0) {
            return $segments->first();
        }

        $roll = random_int(1, $total);
        $cumulative = 0;
        foreach ($segments as $segment) {
            $cumulative += $segment->weight;
            if ($roll <= $cumulative) {
                return $segment;
            }
        }

        return $segments->last();
    }

    /** Whether the user can spin today. Developers can always spin. */
    public function canSpin(int $userId): bool
    {
        if ($this->isDeveloper($userId)) {
            return true;
        }

        return ! SpinAttempt::query()
            ->where('user_id', $userId)
            ->whereDate('spun_at', today())
            ->exists();
    }

    /** Whether $userId is a QA account allowed to spin unlimited times. */
    protected function isDeveloper(int $userId): bool
    {
        $email = User::query()->whereKey($userId)->value('email');

        return $email !== null && \in_array($email, self::DEVELOPER_EMAILS, true);
    }
}
