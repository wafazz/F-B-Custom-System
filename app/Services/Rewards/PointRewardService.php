<?php

namespace App\Services\Rewards;

use App\Models\PointReward;
use App\Models\PointRewardClaim;
use App\Models\PointTransaction;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PointRewardService
{
    public function __construct(protected LoyaltyService $loyalty)
    {
    }

    /**
     * Atomically claim a point reward. Validates per-user cap, global cap,
     * and the validity window, then credits the loyalty balance with an
     * `adjustment` point transaction.
     */
    public function claim(PointReward $reward, int $userId): PointRewardClaim
    {
        $now = now();
        if ($reward->status !== 'active') {
            throw new RuntimeException('This reward is not active.');
        }
        if ($reward->valid_from !== null && $reward->valid_from->greaterThan($now)) {
            throw new RuntimeException('This reward is not yet available.');
        }
        if ($reward->valid_until !== null && $reward->valid_until->lessThan($now)) {
            throw new RuntimeException('This reward has expired.');
        }

        return DB::transaction(function () use ($reward, $userId) {
            // Re-fetch with row lock so concurrent claims can't bypass the
            // total cap.
            /** @var PointReward $locked */
            $locked = PointReward::query()->lockForUpdate()->findOrFail($reward->id);

            if ($locked->max_total_claims !== null && $locked->claimed_count >= $locked->max_total_claims) {
                throw new RuntimeException('All rewards have been claimed.');
            }

            $userClaims = PointRewardClaim::query()
                ->where('point_reward_id', $locked->id)
                ->where('user_id', $userId)
                ->count();
            if ($userClaims >= $locked->max_claims_per_user) {
                throw new RuntimeException('You have already claimed this reward.');
            }

            $balance = $this->loyalty->balance($userId);
            PointTransaction::create([
                'user_id' => $userId,
                'type' => 'adjustment',
                'points' => $locked->points,
                'balance_after' => $balance + $locked->points,
                'reason' => "reward: {$locked->name}",
            ]);

            $locked->increment('claimed_count');

            return PointRewardClaim::create([
                'point_reward_id' => $locked->id,
                'user_id' => $userId,
                'points' => $locked->points,
                'claimed_at' => now(),
            ]);
        });
    }
}
