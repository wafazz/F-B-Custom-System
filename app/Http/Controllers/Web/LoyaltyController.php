<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CustomerTier;
use App\Models\MembershipTier;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyController extends Controller
{
    public function show(Request $request, LoyaltyService $loyalty): Response
    {
        /** @var User $user */
        $user = $request->user();
        $balance = $loyalty->balance($user->id);

        $tier = CustomerTier::with('tier')->where('user_id', $user->id)->first();
        $current = $tier?->tier;
        $currentSpend = $tier instanceof CustomerTier ? (float) $tier->lifetime_spend : 0.0;
        $next = MembershipTier::query()
            ->where('min_lifetime_spend', '>', $currentSpend)
            ->orderBy('min_lifetime_spend')
            ->first();

        $history = PointTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get();

        $rows = [];
        foreach ($history as $row) {
            $rows[] = [
                'id' => $row->id,
                'type' => $row->type,
                'points' => $row->points,
                'balance_after' => $row->balance_after,
                'reason' => $row->reason,
                'created_at' => $row->created_at?->toIso8601String(),
            ];
        }

        return Inertia::render('storefront/loyalty', [
            'balance' => $balance,
            'redeem_value' => $loyalty->dollarsForPoints($balance),
            'lifetime_spend' => $tier ? (float) $tier->lifetime_spend : 0.0,
            'current_tier' => $current ? [
                'name' => $current->name,
                'multiplier' => (float) $current->earn_multiplier,
                'color' => $current->color,
            ] : null,
            'next_tier' => $next ? [
                'name' => $next->name,
                'min_spend' => (float) $next->min_lifetime_spend,
                'multiplier' => (float) $next->earn_multiplier,
            ] : null,
            'history' => $rows,
        ]);
    }
}
