<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $rows = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($row): array {
                /** @var array<string, mixed> $data */
                $data = $row->data;

                return [
                    'id' => (string) $row->id,
                    'type' => (string) ($data['type'] ?? 'info'),
                    'title' => (string) ($data['title'] ?? 'Notification'),
                    'body' => (string) ($data['body'] ?? ''),
                    'url' => isset($data['url']) ? (string) $data['url'] : null,
                    'read_at' => $row->read_at?->toIso8601String(),
                    'created_at' => $row->created_at->toIso8601String(),
                    'human_time' => $row->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('storefront/notifications', [
            'items' => $rows,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $user->notifications()->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $user->unreadNotifications->markAsRead();

        return back();
    }
}
