<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\PointRewardClaim;
use App\Services\Rewards\PointRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RewardPickupController extends Controller
{
    public function index(): JsonResponse
    {
        $pending = PointRewardClaim::query()
            ->whereNull('fulfilled_at')
            ->with(['pointReward:id,name,banner_image,kind', 'pointReward.product:id,name', 'user:id,name,phone'])
            ->latest('claimed_at')
            ->limit(100)
            ->get()
            ->map(fn (PointRewardClaim $c) => $this->present($c))
            ->values();

        $fulfilledToday = PointRewardClaim::query()
            ->whereNotNull('fulfilled_at')
            ->whereDate('fulfilled_at', today())
            ->with(['pointReward:id,name,kind', 'user:id,name'])
            ->latest('fulfilled_at')
            ->limit(20)
            ->get()
            ->map(fn (PointRewardClaim $c) => $this->present($c))
            ->values();

        return response()->json([
            'pending' => $pending,
            'fulfilled_today' => $fulfilledToday,
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $code = strtoupper(trim((string) $request->query('code', '')));
        if ($code === '') {
            return response()->json(['message' => 'Enter a pickup code.'], 422);
        }

        $claim = PointRewardClaim::query()
            ->where('pickup_code', $code)
            ->with(['pointReward:id,name,banner_image,kind', 'pointReward.product:id,name', 'user:id,name,phone'])
            ->first();

        if ($claim === null) {
            return response()->json(['message' => "No pickup found for {$code}."], 404);
        }

        return response()->json($this->present($claim));
    }

    public function fulfil(PointRewardClaim $pickup, PointRewardService $service): JsonResponse
    {
        try {
            $service->fulfil($pickup);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pickup->refresh()->load(['pointReward:id,name,banner_image,kind', 'pointReward.product:id,name', 'user:id,name,phone']);

        return response()->json($this->present($pickup));
    }

    /** @return array<string, mixed> */
    protected function present(PointRewardClaim $c): array
    {
        return [
            'id' => $c->id,
            'pickup_code' => $c->pickup_code,
            'points_spent' => $c->points_spent,
            'claimed_at' => $c->claimed_at->toIso8601String(),
            'fulfilled_at' => $c->fulfilled_at?->toIso8601String(),
            'reward' => $c->pointReward ? [
                'name' => $c->pointReward->name,
                'banner_image' => $c->pointReward->banner_image,
                'kind' => $c->pointReward->kind,
                'product_name' => $c->pointReward->product?->name,
            ] : null,
            'customer' => $c->user ? [
                'name' => $c->user->name,
                'phone' => $c->user->phone,
            ] : null,
        ];
    }
}
