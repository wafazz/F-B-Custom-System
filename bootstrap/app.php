<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\TrackUserPresence::class,
        ]);
        $middleware->statefulApi();
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // PWA push subscribe + order placement are session-authenticated and
        // SameSite=Lax already blocks cross-site forging; the CSRF round-trip
        // is fragile on iOS PWAs where cached HTML serves a stale meta-token
        // after login and the XSRF-TOKEN cookie can expire while the app
        // stays warm in standalone mode.
        $middleware->validateCsrfTokens(except: [
            'api/push/subscribe',
            'api/orders',
        ]);
        $middleware->alias([
            'pos' => \App\Http\Middleware\EnsurePosSession::class,
            'pos.token' => \App\Http\Middleware\EnsurePosToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $exception, $request) {
            if (! app()->environment('local') && in_array($response->getStatusCode(), [403, 404, 500, 503])) {
                // JSON / XHR clients (mobile POS, API consumers) need a JSON
                // error body — otherwise they get HTML and blow up parsing it.
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => $exception->getMessage() ?: 'Server error.',
                    ], $response->getStatusCode());
                }

                return inertia('errors/error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            if ($response->getStatusCode() === 419) {
                // JSON / XHR clients (PWA fetch, mobile API) can't follow the
                // back() redirect and end up reporting a false 200. Return a
                // real 419 JSON so the client can surface the failure.
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => 'CSRF token mismatch.'], 419);
                }

                return back()->with(['error' => 'The page expired, please try again.']);
            }

            return $response;
        });
    })->create();
