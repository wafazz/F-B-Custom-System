<?php

namespace App\Services\Rewards;

use App\Models\PointReward;
use App\Models\PointRewardClaim;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PointRewardService
{
    public function __construct(protected LoyaltyService $loyalty)
    {
    }

    /**
     * Customer spends points to redeem a catalogued item (menu product or
     * merchandise). Atomic with the loyalty debit; issues a short pickup
     * code that staff scan/type at the counter to fulfil.
     */
    public function redeem(PointReward $reward, int $userId): PointRewardClaim
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
            /** @var PointReward $locked */
            $locked = PointReward::query()->lockForUpdate()->findOrFail($reward->id);

            if ($locked->stock !== null && $locked->claimed_count >= $locked->stock) {
                throw new RuntimeException('Out of stock — try another reward.');
            }

            $userClaims = PointRewardClaim::query()
                ->where('point_reward_id', $locked->id)
                ->where('user_id', $userId)
                ->count();
            if ($userClaims >= $locked->max_claims_per_user) {
                throw new RuntimeException('You have already redeemed this reward.');
            }

            $balance = $this->loyalty->balance($userId);
            if ($balance < $locked->points_cost) {
                throw new RuntimeException("You need {$locked->points_cost} pts. (Current: {$balance})");
            }

            $this->loyalty->redeem(
                $userId,
                $locked->points_cost,
                null,
                "redeem reward: {$locked->name}",
            );

            $locked->increment('claimed_count');

            return PointRewardClaim::create([
                'point_reward_id' => $locked->id,
                'user_id' => $userId,
                'points_spent' => $locked->points_cost,
                'pickup_code' => $this->uniquePickupCode(),
                'claimed_at' => now(),
            ]);
        });
    }

    public function fulfil(PointRewardClaim $claim): PointRewardClaim
    {
        if ($claim->fulfilled_at !== null) {
            throw new RuntimeException('Already fulfilled.');
        }
        $claim->forceFill(['fulfilled_at' => now()])->save();

        return $claim;
    }

    protected function uniquePickupCode(): string
    {
        do {
            $code = 'R-'.strtoupper(Str::random(6));
        } while (PointRewardClaim::query()->where('pickup_code', $code)->exists());

        return $code;
    }
}
