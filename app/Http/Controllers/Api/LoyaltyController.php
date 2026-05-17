<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerTier;
use App\Models\MembershipTier;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function show(Request $request, LoyaltyService $loyalty): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = (int) $user->getKey();

        $balance = $loyalty->balance($userId);
        $tierRow = CustomerTier::with('tier')->where('user_id', $userId)->first();
        $current = $tierRow?->tier;
        $lifetimeSpend = $tierRow ? (float) $tierRow->lifetime_spend : 0.0;
        $next = MembershipTier::query()
            ->where('min_lifetime_spend', '>', $lifetimeSpend)
            ->orderBy('min_lifetime_spend')
            ->first();

        return response()->json([
            'balance' => $balance,
            'redeem_value_rm' => $loyalty->dollarsForPoints($balance),
            'points_per_ringgit' => LoyaltyService::POINTS_PER_RINGGIT,
            'points_per_ringgit_redeemed' => LoyaltyService::POINTS_PER_RINGGIT_REDEEMED,
            'lifetime_spend' => $lifetimeSpend,
            'tier' => $current ? [
                'id' => $current->id,
                'name' => $current->name,
                'color' => $current->color,
                'multiplier' => (float) $current->earn_multiplier,
            ] : null,
            'next_tier' => $next ? [
                'name' => $next->name,
                'min_spend' => (float) $next->min_lifetime_spend,
                'multiplier' => (float) $next->earn_multiplier,
            ] : null,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rows = PointTransaction::query()
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->limit(100)
            ->get(['id', 'type', 'points', 'balance_after', 'reason', 'order_id', 'created_at']);

        return response()->json([
            'transactions' => $rows->map(fn (PointTransaction $t) => [
                'id' => $t->id,
                'type' => $t->type,
                'points' => $t->points,
                'balance_after' => $t->balance_after,
                'reason' => $t->reason,
                'order_id' => $t->order_id,
                'created_at' => $t->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
