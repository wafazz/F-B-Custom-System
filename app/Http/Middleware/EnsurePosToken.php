<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the Sanctum bearer carries the `pos` ability and (when the
 * route has a {branch} binding) that the branch matches what the token
 * was issued for. Token name format: `pos:{branch_code}`.
 */
class EnsurePosToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $token || ! in_array('pos', (array) $token->abilities, true)) {
            abort(403, 'POS token required.');
        }

        if (! preg_match('/^pos:(.+)$/', (string) $token->name, $m)) {
            abort(403, 'Invalid POS token name.');
        }
        $tokenBranchCode = $m[1];

        $branch = $request->route('branch');
        if ($branch instanceof Branch && $branch->code !== $tokenBranchCode) {
            abort(403, 'Token branch mismatch.');
        }

        $request->attributes->set('pos_branch_code', $tokenBranchCode);

        return $next($request);
    }
}
