<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CustomerTier;
use App\Models\HomeSlide;
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

        return Inertia::render('storefront/loyalty', [
            'slides' => $slides,
            'referral' => [
                'code' => $user->referral_code,
                'share_url' => url('/register').'?ref='.$user->referral_code,
                'referrer_bonus' => (int) config('services.referral.referrer_bonus_points', 100),
            ],
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
            'membership_tiers' => $tiers,
        ]);
    }
}
