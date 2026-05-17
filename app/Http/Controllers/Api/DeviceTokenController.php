<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['ios', 'android', 'web'])],
            'token' => ['required', 'string', 'max:512'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:40'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $device = DeviceToken::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'token' => $data['token']],
            [
                'platform' => $data['platform'],
                'device_id' => $data['device_id'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'device' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'device_name' => $device->device_name,
                'app_version' => $device->app_version,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        /** @var User $user */
        $user = $request->user();

        DeviceToken::query()
            ->where('user_id', $user->getKey())
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
