<?php

namespace App\Services\Loyalty;

use App\Models\CustomerTier;
use App\Models\MembershipTier;
use App\Models\Order;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoyaltyService
{
    /** Earn rate: 1 point per RM 1.00 spent (post-discount, pre-SST). */
    public const POINTS_PER_RINGGIT = 1;

    /** Redeem rate: 100 points = RM 1.00 discount. */
    public const POINTS_PER_RINGGIT_REDEEMED = 100;

    public function balance(int $userId): int
    {
        $latest = PointTransaction::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        if (! $latest instanceof PointTransaction) {
            return 0;
        }

        return $latest->balance_after;
    }

    public function earnFromOrder(Order $order): ?PointTransaction
    {
        if ($order->user_id === null || (float) $order->subtotal <= 0) {
            return null;
        }

        $multiplier = $this->multiplierFor($order->user_id);
        $base = (int) floor((float) $order->subtotal * self::POINTS_PER_RINGGIT * $multiplier);
        if ($base <= 0) {
            return null;
        }

        return $this->record($order->user_id, 'earn', $base, $order, "earn from {$order->number}");
    }

    public function redeem(int $userId, int $points, ?Order $order = null, ?string $reason = null): PointTransaction
    {
        if ($points <= 0) {
            throw new RuntimeException('Redeem amount must be positive.');
        }
        if ($this->balance($userId) < $points) {
            throw new RuntimeException('Insufficient points.');
        }

        return $this->record($userId, 'redeem', -$points, $order, $reason ?? 'redeem at checkout');
    }

    public function refundFromOrder(Order $order): void
    {
        if ($order->user_id === null) {
            return;
        }
        // Reverse all earns and redeems tied to this order.
        $rows = PointTransaction::query()
            ->where('order_id', $order->id)
            ->whereIn('type', ['earn', 'redeem'])
            ->get();
        foreach ($rows as $row) {
            $this->record(
                $order->user_id,
                'refund',
                -$row->points,
                $order,
                "refund {$row->type} from {$order->number}",
            );
        }
    }

    public function applyTierUpgrade(int $userId, float $orderTotal): ?MembershipTier
    {
        return DB::transaction(function () use ($userId, $orderTotal) {
            /** @var CustomerTier $row */
            $row = CustomerTier::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $userId],
                [
                    'membership_tier_id' => MembershipTier::query()->orderBy('min_lifetime_spend')->value('id') ?? 1,
                    'lifetime_spend' => 0,
                    'achieved_at' => now(),
                ],
            );

            $newSpend = (float) $row->lifetime_spend + $orderTotal;
            $next = MembershipTier::tierForSpend($newSpend);

            $changed = $next instanceof MembershipTier && $next->id !== $row->membership_tier_id;
            $row->forceFill([
                'lifetime_spend' => $newSpend,
                'membership_tier_id' => $next instanceof MembershipTier ? $next->id : $row->membership_tier_id,
                'achieved_at' => $changed ? now() : $row->achieved_at,
            ])->save();

            return $changed ? $next : null;
        });
    }

    public function multiplierFor(int $userId): float
    {
        $row = CustomerTier::query()->where('user_id', $userId)->first();
        if (! $row) {
            return 1.0;
        }
        $tier = MembershipTier::find($row->membership_tier_id);

        return $tier ? (float) $tier->earn_multiplier : 1.0;
    }

    public function dollarsForPoints(int $points): float
    {
        return round($points / self::POINTS_PER_RINGGIT_REDEEMED, 2);
    }

    public function pointsForDollars(float $dollars): int
    {
        return (int) round($dollars * self::POINTS_PER_RINGGIT_REDEEMED);
    }

    protected function record(int $userId, string $type, int $points, ?Order $order, ?string $reason): PointTransaction
    {
        return DB::transaction(function () use ($userId, $type, $points, $order, $reason) {
            $current = $this->balance($userId);
            $balance = max(0, $current + $points);

            return PointTransaction::create([
                'user_id' => $userId,
                'type' => $type,
                'points' => $points,
                'balance_after' => $balance,
                'order_id' => $order?->id,
                'reason' => $reason,
            ]);
        });
    }
}
