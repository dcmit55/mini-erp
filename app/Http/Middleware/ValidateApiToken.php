<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiToken;

class ValidateApiToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get token from header
        $token = $request->header('x-api-key');

        // Check if token exists
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'API token is required.',
            ], 401);
        }

        // Validate token
        if (!ApiToken::isValid($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API token.',
            ], 401);
        }

        // Optional: IP whitelist check
        $apiToken = ApiToken::where('token', $token)->first();
        if ($apiToken && !$apiToken->isIpAllowed($request->ip())) {
            return response()->json([
                'success' => false,
                'message' => 'Your IP address is not allowed.',
            ], 403);
        }

        return $next($request);
    }
}