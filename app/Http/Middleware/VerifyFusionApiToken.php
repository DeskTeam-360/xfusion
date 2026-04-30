<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyFusionApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('fusion_api.token');

        if ($token === null || $token === '') {
            return $next($request);
        }

        $bearer = $request->bearerToken();
        if (! is_string($bearer) || ! hash_equals((string) $token, $bearer)) {
            return response()->json([
                'message' => 'Invalid or missing API token.',
            ], 401);
        }

        return $next($request);
    }
}
