<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    public function index(): JsonResponse
    {
        $branches = Branch::active()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'address', 'city', 'state', 'phone', 'latitude', 'longitude', 'operating_hours', 'logo', 'cover_image'])
            ->map(fn (Branch $b) => $this->present($b))
            ->values();

        return response()->json(['branches' => $branches]);
    }

    public function show(Branch $branch): JsonResponse
    {
        return response()->json(['branch' => $this->present($branch)]);
    }

    /** @return array<string, mixed> */
    protected function present(Branch $b): array
    {
        return [
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
            'cover_image' => $b->cover_image,
            'is_open_now' => $b->isOpenNow(),
            'accepts_orders' => (bool) $b->accepts_orders,
            'sst_rate' => (float) $b->sst_rate,
            'sst_enabled' => (bool) $b->sst_enabled,
            'service_charge_rate' => (float) $b->service_charge_rate,
            'service_charge_enabled' => (bool) $b->service_charge_enabled,
        ];
    }
}
