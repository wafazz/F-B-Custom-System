<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointReward;
use App\Models\PointRewardClaim;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Rewards\PointRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PointRewardController extends Controller
{
    public function index(Request $request, LoyaltyService $loyalty): JsonResponse
    {
        $userId = (int) $request->user()->getKey();

        $rewards = PointReward::active()
            ->with('product:id,name')
            ->orderBy('points_cost')
            ->get();

        $userClaimCounts = PointRewardClaim::query()
            ->where('user_id', $userId)
            ->selectRaw('point_reward_id, COUNT(*) as c')
            ->groupBy('point_reward_id')
            ->pluck('c', 'point_reward_id');

        $pending = PointRewardClaim::query()
            ->where('user_id', $userId)
            ->whereNull('fulfilled_at')
            ->with('pointReward:id,name,banner_image,kind')
            ->latest('claimed_at')
            ->get()
            ->map(fn (PointRewardClaim $c) => [
                'id' => $c->id,
                'pickup_code' => $c->pickup_code,
                'points_spent' => $c->points_spent,
                'claimed_at' => $c->claimed_at->toIso8601String(),
                'reward' => $c->pointReward ? [
                    'name' => $c->pointReward->name,
                    'banner_image' => $c->pointReward->banner_image,
                    'kind' => $c->pointReward->kind,
                ] : null,
            ])
            ->values();

        return response()->json([
            'rewards' => $rewards->map(fn (PointReward $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'description' => $r->description,
                'banner_image' => $r->banner_image,
                'points_cost' => $r->points_cost,
                'kind' => $r->kind,
                'product_name' => $r->product?->name,
                'max_claims_per_user' => $r->max_claims_per_user,
                'user_claims' => (int) ($userClaimCounts[$r->id] ?? 0),
                'stock' => $r->stock,
                'claimed_count' => $r->claimed_count,
                'valid_until' => $r->valid_until?->toIso8601String(),
            ])->values(),
            'pending' => $pending,
            'points_balance' => (int) $loyalty->balance($userId),
        ]);
    }

    public function redeem(Request $request, PointReward $pointReward, PointRewardService $service): JsonResponse
    {
        try {
            $claim = $service->redeem($pointReward, (int) $request->user()->getKey());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'id' => $claim->id,
            'pickup_code' => $claim->pickup_code,
            'points_spent' => $claim->points_spent,
            'message' => "Redeemed! Show pickup code {$claim->pickup_code} at the counter.",
        ]);
    }
}
