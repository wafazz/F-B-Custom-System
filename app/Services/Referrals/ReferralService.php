<?php

namespace App\Services\Referrals;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\ReferralReward;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function __construct(protected LoyaltyService $loyalty) {}

    /**
     * Award referrer + referee bonus on the referee's first completed order.
     * Idempotent — uses unique(referee_user_id) on referral_rewards.
     */
    public function maybeAwardForCompletedOrder(Order $order): ?ReferralReward
    {
        if ($order->user_id === null) {
            return null;
        }

        // Referral bonus only fires on orders that were actually paid for.
        if ($order->payment_status !== PaymentStatus::Paid) {
            return null;
        }

        /** @var User|null $referee */
        $referee = User::find($order->user_id);
        if (! $referee || ! $referee->referred_by) {
            return null;
        }

        $referrer = User::find($referee->referred_by);
        if (! $referrer || $referrer->getKey() === $referee->getKey()) {
            return null;
        }

        if (ReferralReward::query()->where('referee_user_id', $referee->getKey())->exists()) {
            return null;
        }

        $referrerPoints = (int) config('services.referral.referrer_bonus_points', 100);
        $refereePoints = (int) config('services.referral.referee_bonus_points', 100);

        return DB::transaction(function () use ($referrer, $referee, $order, $referrerPoints, $refereePoints) {
            /** @var ReferralReward $reward */
            $reward = ReferralReward::create([
                'referrer_user_id' => $referrer->getKey(),
                'referee_user_id' => $referee->getKey(),
                'order_id' => $order->id,
                'referrer_points' => $referrerPoints,
                'referee_points' => $refereePoints,
            ]);

            // Bonus credits as adjustment-type point rows.
            if ($referrerPoints > 0) {
                PointTransaction::create([
                    'user_id' => $referrer->getKey(),
                    'type' => 'adjustment',
                    'points' => $referrerPoints,
                    'balance_after' => $this->loyalty->balance($referrer->getKey()) + $referrerPoints,
                    'order_id' => $order->id,
                    'reason' => "referral bonus — {$referee->name} ordered",
                ]);
            }
            if ($refereePoints > 0) {
                PointTransaction::create([
                    'user_id' => $referee->getKey(),
                    'type' => 'adjustment',
                    'points' => $refereePoints,
                    'balance_after' => $this->loyalty->balance($referee->getKey()) + $refereePoints,
                    'order_id' => $order->id,
                    'reason' => "welcome bonus — referred by {$referrer->name}",
                ]);
            }

            return $reward;
        });
    }
}
