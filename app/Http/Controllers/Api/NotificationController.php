<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $items = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (DatabaseNotification $row): array {
                /** @var array<string, mixed> $data */
                $data = $row->data;

                return [
                    'id' => (string) $row->id,
                    'type' => (string) ($data['type'] ?? 'info'),
                    'title' => (string) ($data['title'] ?? 'Notification'),
                    'body' => (string) ($data['body'] ?? ''),
                    'url' => isset($data['url']) ? (string) $data['url'] : null,
                    'read_at' => $row->read_at?->toIso8601String(),
                    'created_at' => $row->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'notifications' => $items,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->notifications()
            ->where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'ok' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        return response()->json(['ok' => true, 'unread_count' => 0]);
    }
}
