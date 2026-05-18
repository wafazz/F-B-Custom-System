<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PointReward;
use App\Models\PointRewardClaim;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Rewards\PointRewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PointRewardController extends Controller
{
    public function index(Request $request, LoyaltyService $loyalty): Response
    {
        $userId = (int) $request->user()->getKey();

        $rewards = PointReward::active()->latest()->get();

        $claimedCounts = PointRewardClaim::query()
            ->where('user_id', $userId)
            ->selectRaw('point_reward_id, COUNT(*) as c')
            ->groupBy('point_reward_id')
            ->pluck('c', 'point_reward_id');

        return Inertia::render('storefront/point-rewards', [
            'rewards' => $rewards->map(fn (PointReward $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'description' => $r->description,
                'banner_image' => $r->banner_image,
                'points' => $r->points,
                'max_claims_per_user' => $r->max_claims_per_user,
                'user_claims' => (int) ($claimedCounts[$r->id] ?? 0),
                'valid_until' => $r->valid_until?->toIso8601String(),
            ])->values(),
            'points_balance' => (int) $loyalty->balance($userId),
        ]);
    }

    public function claim(Request $request, PointReward $pointReward, PointRewardService $service): RedirectResponse
    {
        try {
            $service->claim($pointReward, (int) $request->user()->getKey());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "+{$pointReward->points} pts credited.");
    }
}
