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
            // status + accepts_orders are required by Branch::isOpenNow(); without
            // them the method short-circuits to false and every branch reads "Closed".
            ->get(['id', 'code', 'name', 'address', 'city', 'state', 'phone', 'latitude', 'longitude', 'operating_hours', 'logo', 'cover_image', 'status', 'accepts_orders', 'avg_rating', 'reviews_count'])
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
            'closed_reason' => $b->closedReason(),
            'todays_hours' => $this->todaysHours($b),
            'avg_rating' => (float) $b->avg_rating,
            'reviews_count' => (int) $b->reviews_count,
            'accepts_orders' => (bool) $b->accepts_orders,
            'sst_rate' => (float) $b->sst_rate,
            'sst_enabled' => (bool) $b->sst_enabled,
            'service_charge_rate' => (float) $b->service_charge_rate,
            'service_charge_enabled' => (bool) $b->service_charge_enabled,
        ];
    }

    /** Today's "open – close" label, or null when closed/unset for today. */
    protected function todaysHours(Branch $b): ?string
    {
        // getAttribute() applies the 'array' cast at runtime and is typed mixed,
        // so is_array() narrows cleanly (the property's static type is string|null).
        $hours = $b->getAttribute('operating_hours');
        if (! is_array($hours)) {
            return null;
        }
        $today = $hours[strtolower(now()->englishDayOfWeek)] ?? null;
        if (! is_array($today) || empty($today['enabled'])) {
            return null;
        }
        $open = (string) ($today['open'] ?? '');
        $close = (string) ($today['close'] ?? '');
        if ($open === '' || $close === '') {
            return null;
        }

        return $open.' – '.$close;
    }
}
