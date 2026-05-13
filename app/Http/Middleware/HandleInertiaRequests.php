<?php

namespace App\Http\Middleware;

use App\Models\PosShift;
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
        ];
    }
}
