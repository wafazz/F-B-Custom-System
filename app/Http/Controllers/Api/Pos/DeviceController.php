<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['ios', 'android', 'fcm', 'apns'])],
            'token' => ['required', 'string', 'max:512'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:40'],
        ]);

        // FCM/APNs are the expo-notifications type values; normalize to the
        // platform enum used by the device_tokens table.
        $platform = match ($data['platform']) {
            'fcm' => 'android',
            'apns' => 'ios',
            default => $data['platform'],
        };

        /** @var User $user */
        $user = $request->user();

        $branchCode = (string) $request->attributes->get('pos_branch_code');
        $branch = Branch::query()->where('code', $branchCode)->firstOrFail();

        // One physical device = one current POS row. If the same FCM token
        // was previously bound to a different staff (e.g. shift swap), drop
        // those rows first so we don't double-push the same handset.
        DeviceToken::query()
            ->where('scope', DeviceToken::SCOPE_POS)
            ->where('token', $data['token'])
            ->where('user_id', '!=', $user->getKey())
            ->delete();

        $device = DeviceToken::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'token' => $data['token']],
            [
                'scope' => DeviceToken::SCOPE_POS,
                'branch_id' => $branch->id,
                'platform' => $platform,
                'device_id' => $data['device_id'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'device' => [
                'id' => $device->id,
                'branch_code' => $branch->code,
                'platform' => $device->platform,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(Request $request, string $token): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        DeviceToken::query()
            ->where('user_id', $user->getKey())
            ->where('scope', DeviceToken::SCOPE_POS)
            ->where('token', $token)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
