<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyVPeopleIntegrationToken
{
    public function handle(Request $request, Closure $next)
    {
        $expectedToken = trim((string) config('services.vpeople.integration_token'));
        $providedToken = trim((string) $request->bearerToken());

        if ($expectedToken === '' || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses integrasi tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}
