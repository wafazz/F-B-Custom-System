<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PwaInstall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaInstallController extends Controller
{
    public function record(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_fingerprint' => ['required', 'string', 'min:8', 'max:64'],
            'platform' => ['nullable', 'string', 'max:40'],
        ]);

        $userAgent = substr((string) $request->userAgent(), 0, 255);
        $userId = $request->user()?->getKey();

        PwaInstall::query()->updateOrCreate(
            ['device_fingerprint' => $data['device_fingerprint']],
            [
                'user_id' => $userId,
                'user_agent' => $userAgent,
                'platform' => $data['platform'] ?? null,
                'installed_at' => now(),
                'last_active_at' => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_fingerprint' => ['required', 'string', 'min:8', 'max:64'],
        ]);

        PwaInstall::query()
            ->where('device_fingerprint', $data['device_fingerprint'])
            ->update([
                'last_active_at' => now(),
                'user_id' => $request->user()?->getKey(),
            ]);

        return response()->json(['ok' => true]);
    }
}
