<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $rewards = ReferralReward::query()
            ->with('referee:id,name,email')
            ->where('referrer_user_id', $user->getKey())
            ->latest()
            ->get();

        $rows = [];
        foreach ($rewards as $r) {
            $referee = $r->referee;
            $rows[] = [
                'id' => $r->id,
                'referee_name' => $referee instanceof User ? $referee->name : '—',
                'points_earned' => $r->referrer_points,
                'created_at' => $r->created_at?->toIso8601String(),
            ];
        }

        return Inertia::render('storefront/referral', [
            'code' => $user->referral_code,
            'share_url' => url('/register').'?ref='.$user->referral_code,
            'referrer_bonus' => (int) config('services.referral.referrer_bonus_points', 100),
            'referee_bonus' => (int) config('services.referral.referee_bonus_points', 100),
            'rewards' => $rows,
            'total_earned' => array_sum(array_column($rows, 'points_earned')),
        ]);
    }
}
