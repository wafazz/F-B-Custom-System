<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerTier;
use App\Models\HomeSlide;
use App\Models\MembershipTier;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    /**
     * Full loyalty-page payload — mirrors the Inertia LoyaltyController@show so
     * the native Membership tab renders the same slides, tier cards, balance,
     * referral and history.
     */
    public function page(Request $request, LoyaltyService $loyalty): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = (int) $user->getKey();
        $balance = $loyalty->balance($userId);

        $tier = CustomerTier::with('tier')->where('user_id', $userId)->first();
        $current = $tier?->tier;
        $currentSpend = $tier instanceof CustomerTier ? (float) $tier->lifetime_spend : 0.0;
        $next = MembershipTier::query()
            ->where('min_lifetime_spend', '>', $currentSpend)
            ->orderBy('min_lifetime_spend')
            ->first();

        $history = PointTransaction::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (PointTransaction $row) => [
                'id' => $row->id,
                'type' => $row->type,
                'points' => $row->points,
                'balance_after' => $row->balance_after,
                'reason' => $row->reason,
                'created_at' => $row->created_at?->toIso8601String(),
            ])
            ->values();

        $tiers = MembershipTier::query()
            ->orderBy('min_lifetime_spend')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'min_lifetime_spend', 'earn_multiplier', 'color', 'badge_image', 'perks'])
            ->map(fn (MembershipTier $t) => [
                'id' => (int) $t->id,
                'name' => $t->name,
                'min_lifetime_spend' => (float) $t->min_lifetime_spend,
                'earn_multiplier' => (float) $t->earn_multiplier,
                'color' => $t->color,
                'badge_image' => $t->badge_image,
                'perks' => is_array($t->perks) ? array_values($t->perks) : [],
            ])
            ->values();

        $slides = HomeSlide::query()
            ->active()
            ->placement('loyalty')
            ->where('is_global', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HomeSlide $row) => [
                'type' => 'managed',
                'image' => $row->image,
                'title' => $row->title,
                'subtitle' => $row->subtitle,
                'cta_label' => $row->cta_label,
                'cta_url' => $row->cta_url,
            ])
            ->values()
            ->all();

        if (count($slides) === 0) {
            $slides[] = [
                'type' => 'cover',
                'image' => null,
                'title' => 'Brew More, Earn More',
                'subtitle' => 'Collect points and enjoy exclusive rewards.',
                'cta_label' => null,
                'cta_url' => null,
            ];
        }

        return response()->json([
            'slides' => $slides,
            'referral' => [
                'code' => $user->referral_code,
                'share_url' => url('/register').'?ref='.$user->referral_code,
                'referrer_bonus' => (int) config('services.referral.referrer_bonus_points', 100),
            ],
            'balance' => $balance,
            'redeem_value' => $loyalty->dollarsForPoints($balance),
            'lifetime_spend' => $currentSpend,
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
            'history' => $history,
            'membership_tiers' => $tiers,
        ]);
    }

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
