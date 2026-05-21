<?php

namespace App\Services\CheckIn;

use App\Models\DailyCheckIn;
use App\Models\DailyCheckInReward;
use App\Models\DailyCheckInSetting;
use App\Models\PointTransaction;
use App\Models\VoucherClaim;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Vouchers\VoucherService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DailyCheckInService
{
    public function __construct(
        protected LoyaltyService $loyalty,
        protected VoucherService $vouchers,
    ) {
    }

    public function checkIn(int $userId): DailyCheckIn
    {
        return DB::transaction(function () use ($userId) {
            $today = today();

            $existing = DailyCheckIn::query()
                ->where('user_id', $userId)
                ->whereDate('check_in_date', $today)
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                throw new RuntimeException('You have already checked in today. Come back tomorrow!');
            }

            $settings = DailyCheckInSetting::current();
            $dayNumber = $this->nextDayNumber($userId, $today, $settings);

            $reward = DailyCheckInReward::query()
                ->where('is_active', true)
                ->where('day_number', $dayNumber)
                ->first();

            $awardedPoints = 0;
            $voucherClaimId = null;
            $rewardType = $reward?->reward_type ?? 'points';

            if ($reward && $reward->reward_type === 'points' && $reward->points !== null && $reward->points > 0) {
                $balance = $this->loyalty->balance($userId);
                PointTransaction::create([
                    'user_id' => $userId,
                    'type' => 'adjustment',
                    'points' => $reward->points,
                    'balance_after' => $balance + $reward->points,
                    'reason' => "daily check-in day {$dayNumber}",
                ]);
                $awardedPoints = $reward->points;
            }

            if ($reward && $reward->reward_type === 'voucher' && $reward->voucher_id !== null && $reward->voucher !== null) {
                $alreadyClaimed = VoucherClaim::query()
                    ->where('voucher_id', $reward->voucher_id)
                    ->where('user_id', $userId)
                    ->exists();
                if (! $alreadyClaimed) {
                    try {
                        $claim = $this->vouchers->claim($reward->voucher, $userId);
                        $voucherClaimId = $claim->id;
                    } catch (\Throwable) {
                        // Eligibility/cap trips — record the check-in anyway so
                        // the user's streak still advances.
                    }
                }
            }

            return DailyCheckIn::create([
                'user_id' => $userId,
                'check_in_date' => $today,
                'day_number_awarded' => $dayNumber,
                'reward_type' => $rewardType,
                'awarded_points' => $awardedPoints,
                'voucher_claim_id' => $voucherClaimId,
            ]);
        });
    }

    public function canCheckInToday(int $userId): bool
    {
        return ! DailyCheckIn::query()
            ->where('user_id', $userId)
            ->whereDate('check_in_date', today())
            ->exists();
    }

    /**
     * Day this check-in awards, given prior streak + settings.
     * - If reset_on_skip and last check-in wasn't yesterday, restart at 1.
     * - If continuing, advance by 1 and wrap once past max_days.
     */
    public function nextDayNumber(int $userId, Carbon $today, DailyCheckInSetting $settings): int
    {
        $last = DailyCheckIn::query()
            ->where('user_id', $userId)
            ->orderByDesc('check_in_date')
            ->first();
        if ($last === null) {
            return 1;
        }

        $lastDate = $last->check_in_date->startOfDay();
        $todayDate = $today->copy()->startOfDay();
        $diff = (int) $lastDate->diffInDays($todayDate, false);

        if ($diff === 1) {
            $next = $last->day_number_awarded + 1;
            return $next > $settings->max_days ? 1 : $next;
        }

        if ($settings->reset_on_skip) {
            return 1;
        }

        // Skip-tolerant mode: keep advancing from where we left off.
        $next = $last->day_number_awarded + 1;
        return $next > $settings->max_days ? 1 : $next;
    }

    /** Current streak length (consecutive days ending today or yesterday). */
    public function currentStreak(int $userId): int
    {
        $last = DailyCheckIn::query()
            ->where('user_id', $userId)
            ->orderByDesc('check_in_date')
            ->first();
        if ($last === null) {
            return 0;
        }

        $lastDate = $last->check_in_date->startOfDay();
        $today = today();
        $diff = (int) $lastDate->diffInDays($today, false);
        if ($diff > 1) {
            return 0;
        }

        return $last->day_number_awarded;
    }
}
