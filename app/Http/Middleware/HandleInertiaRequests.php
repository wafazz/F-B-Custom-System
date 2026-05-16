<?php

namespace App\Http\Middleware;

use App\Models\CustomerTier;
use App\Models\PosShift;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'receipt' => fn () => $request->session()->get('receipt'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'pos_shift' => function () use ($request) {
                $branchId = (int) $request->session()->get('pos.branch_id');
                if ($branchId === 0) {
                    return null;
                }
                $shift = PosShift::query()->open()->where('branch_id', $branchId)->first();
                if (! $shift) {
                    return null;
                }

                return [
                    'id' => $shift->id,
                    'opened_at' => $shift->opened_at->toIso8601String(),
                ];
            },
            'customer_stats' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return null;
                }

                /** @var WalletService $wallet */
                $wallet = app(WalletService::class);
                /** @var LoyaltyService $loyalty */
                $loyalty = app(LoyaltyService::class);

                $userId = (int) $user->getKey();
                $tierRow = CustomerTier::with('tier')->where('user_id', $userId)->first();
                $lifetimeSpend = $tierRow ? (float) $tierRow->lifetime_spend : 0.0;
                $next = \App\Models\MembershipTier::query()
                    ->where('min_lifetime_spend', '>', $lifetimeSpend)
                    ->orderBy('min_lifetime_spend')
                    ->first();

                return [
                    'wallet_balance' => (float) $wallet->balance($userId),
                    'points' => (int) $loyalty->balance($userId),
                    'lifetime_spend' => $lifetimeSpend,
                    'tier' => $tierRow?->tier ? [
                        'name' => $tierRow->tier->name,
                        'color' => $tierRow->tier->color,
                        'multiplier' => (float) $tierRow->tier->earn_multiplier,
                    ] : null,
                    'next_tier' => $next ? [
                        'name' => $next->name,
                        'min_spend' => (float) $next->min_lifetime_spend,
                    ] : null,
                ];
            },
        ];
    }
}
