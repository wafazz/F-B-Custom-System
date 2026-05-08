<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function splash(): Response
    {
        return Inertia::render('storefront/splash', [
            'hasBranches' => Branch::active()->exists(),
        ]);
    }

    public function selectBranch(): Response
    {
        $branches = Branch::active()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'address', 'city', 'state', 'phone', 'latitude', 'longitude', 'operating_hours', 'logo'])
            ->map(fn (Branch $b) => [
                'id' => $b->id,
                'code' => $b->code,
                'name' => $b->name,
                'address' => $b->address,
                'city' => $b->city,
                'state' => $b->state,
                'phone' => $b->phone,
                'latitude' => $b->latitude !== null ? (float) $b->latitude : null,
                'longitude' => $b->longitude !== null ? (float) $b->longitude : null,
                'operating_hours' => $b->operating_hours,
                'logo' => $b->logo,
                'is_open_now' => $b->isOpenNow(),
            ])
            ->values();

        return Inertia::render('storefront/branch-select', [
            'branches' => $branches,
        ]);
    }

    public function menu(Branch $branch): Response
    {
        return Inertia::render('storefront/menu', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'logo' => $branch->logo,
                'cover_image' => $branch->cover_image,
                'sst_rate' => (float) $branch->sst_rate,
                'sst_enabled' => $branch->sst_enabled,
                'status' => $branch->status,
                'accepts_orders' => $branch->accepts_orders,
                'is_open_now' => $branch->isOpenNow(),
            ],
            'reverb' => [
                'channel' => "branch.{$branch->id}.stock",
                'event' => 'stock.changed',
            ],
        ]);
    }
}
