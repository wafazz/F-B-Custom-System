<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CampaignDelivery;
use App\Models\ScheduledCampaign;
use App\Services\Push\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    private const STAFF_ROLES = [
        'super_admin', 'hq_admin', 'ops_manager', 'mkt_manager',
        'branch_manager', 'cashier', 'barista',
    ];

    /**
     * A client reports the customer's current position. For any active
     * proximity campaign whose outlet is within its radius, push the
     * notification — once per cooldown window. Works for any caller (web
     * foreground or a native geofence event); the server is the source of
     * truth for distance + dedup.
     */
    public function ping(Request $request, PushService $push): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['ok' => false], 401);
        }
        // Proximity campaigns target customers, not staff.
        if ($user->hasAnyRole(self::STAFF_ROLES)) {
            return response()->json(['ok' => true, 'notified' => []]);
        }

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $campaigns = ScheduledCampaign::activeLocationCampaigns();
        if ($campaigns->isEmpty()) {
            return response()->json(['ok' => true, 'notified' => []]);
        }

        $branches = Branch::query()
            ->whereIn('id', $campaigns->pluck('branch_id')->unique())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->keyBy('id');

        $userId = (int) $user->getKey();
        $notified = [];

        foreach ($campaigns as $campaign) {
            $branch = $branches->get($campaign->branch_id);
            if ($branch === null) {
                continue;
            }
            $distance = self::haversineMeters(
                (float) $data['lat'], (float) $data['lng'],
                (float) $branch->latitude, (float) $branch->longitude,
            );
            if ($distance > (int) $campaign->radius_meters) {
                continue;
            }

            // Cooldown: don't re-ping the same customer for this campaign while
            // they linger nearby. delay_minutes doubles as the cooldown.
            $cooldown = $campaign->delay_minutes ?: 360;
            $recentlySent = CampaignDelivery::query()
                ->where('scheduled_campaign_id', $campaign->id)
                ->where('user_id', $userId)
                ->where('sent_at', '>=', now()->subMinutes($cooldown))
                ->exists();
            if ($recentlySent) {
                continue;
            }

            $report = $push->sendToUser($userId, [
                'title' => $campaign->renderMessage((string) $campaign->title, $user, $branch->name),
                'body' => $campaign->renderMessage((string) $campaign->body, $user, $branch->name),
                'url' => $campaign->url ?: '/',
                'tag' => 'geo-'.$campaign->id,
            ]);
            if ($report['sent'] > 0) {
                CampaignDelivery::create([
                    'scheduled_campaign_id' => $campaign->id,
                    'user_id' => $userId,
                    'sent_at' => now(),
                ]);
                $notified[] = (int) $campaign->id;
            }
        }

        return response()->json(['ok' => true, 'notified' => $notified]);
    }

    /** Great-circle distance in metres. */
    private static function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0; // metres
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
