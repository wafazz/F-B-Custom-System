<?php

namespace App\Http\Middleware;

use App\Models\UserPresence;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserPresence
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user !== null) {
            try {
                UserPresence::query()->updateOrCreate(
                    ['user_id' => (int) $user->getKey()],
                    [
                        'last_seen_at' => now(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    ],
                );
            } catch (\Throwable) {
                // Never let presence tracking break the request.
            }
        }

        return $response;
    }
}
