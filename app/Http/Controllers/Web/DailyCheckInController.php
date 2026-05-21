<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DailyCheckIn;
use App\Models\DailyCheckInReward;
use App\Models\DailyCheckInSetting;
use App\Services\CheckIn\DailyCheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DailyCheckInController extends Controller
{
    public function index(Request $request, DailyCheckInService $service): Response
    {
        $userId = (int) $request->user()->getKey();
        $settings = DailyCheckInSetting::current();

        $rewards = DailyCheckInReward::query()
            ->with('voucher:id,name,code')
            ->where('is_active', true)
            ->where('day_number', '<=', $settings->max_days)
            ->orderBy('day_number')
            ->get()
            ->map(fn (DailyCheckInReward $r) => [
                'day_number' => $r->day_number,
                'label' => $r->label,
                'reward_type' => $r->reward_type,
                'points' => $r->points,
                'voucher_name' => $r->voucher?->name,
            ])
            ->values();

        $recent = DailyCheckIn::query()
            ->where('user_id', $userId)
            ->latest('check_in_date')
            ->limit(20)
            ->get()
            ->map(fn (DailyCheckIn $c) => [
                'id' => $c->id,
                'check_in_date' => $c->check_in_date->toDateString(),
                'day_number' => $c->day_number_awarded,
                'reward_type' => $c->reward_type,
                'awarded_points' => $c->awarded_points,
                'voucher_claim_id' => $c->voucher_claim_id,
            ])
            ->values();

        $nextDay = $service->nextDayNumber($userId, today(), $settings);

        return Inertia::render('storefront/check-in', [
            'settings' => [
                'max_days' => $settings->max_days,
                'reset_on_skip' => $settings->reset_on_skip,
            ],
            'rewards' => $rewards,
            'recent' => $recent,
            'can_check_in' => $service->canCheckInToday($userId),
            'current_streak' => $service->currentStreak($userId),
            'next_day' => $nextDay,
        ]);
    }

    public function checkIn(Request $request, DailyCheckInService $service): JsonResponse
    {
        $userId = (int) $request->user()->getKey();

        try {
            $row = $service->checkIn($userId);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'day_number' => $row->day_number_awarded,
            'reward_type' => $row->reward_type,
            'awarded_points' => $row->awarded_points,
            'voucher_claimed' => $row->voucher_claim_id !== null,
            'message' => $this->buildMessage($row),
        ]);
    }

    protected function buildMessage(DailyCheckIn $row): string
    {
        if ($row->awarded_points > 0) {
            return "Day {$row->day_number_awarded} — +{$row->awarded_points} pts credited!";
        }
        if ($row->voucher_claim_id !== null) {
            return "Day {$row->day_number_awarded} — voucher added to your wallet!";
        }

        return "Checked in for day {$row->day_number_awarded}!";
    }
}
