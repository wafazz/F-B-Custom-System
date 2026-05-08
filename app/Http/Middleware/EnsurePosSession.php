<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('pos.user_id') || ! $request->session()->has('pos.branch_id')) {
            return redirect()->route('pos.login');
        }

        return $next($request);
    }
}
