<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $validToken = config('services.api.token');
        $token      = $this->extractBearerToken($request);

        if (!$token) {
            Log::warning('API: No token provided', [
                'ip'                   => $request->ip(),
                'url'                  => $request->fullUrl(),
                'authorization_header' => $request->header('Authorization', '(empty)'),
                'server_http_auth'     => $_SERVER['HTTP_AUTHORIZATION'] ?? '(not set)',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API token is required',
            ], 401);
        }

        if (!hash_equals((string) $validToken, (string) $token)) {
            Log::warning('API: Invalid token', [
                'ip'             => $request->ip(),
                'url'            => $request->fullUrl(),
                'token_provided' => substr($token, 0, 10) . '...',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API token',
            ], 401);
        }

        return $next($request);
    }

    /**
     * Ekstrak Bearer token dari berbagai sumber header.
     * Diperlukan karena PHP built-in server (php artisan serve) di Windows
     * kadang tidak mengisi $_SERVER['HTTP_AUTHORIZATION'].
     */
    private function extractBearerToken(Request $request): ?string
    {
        // Cara 1: Laravel standard via Symfony HttpFoundation
        $token = $request->bearerToken();
        if ($token) {
            return $token;
        }

        // Cara 2: Langsung dari $_SERVER — fallback untuk php artisan serve
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            if (!empty($_SERVER[$key])) {
                if (preg_match('/Bearer\s+(.+)/i', $_SERVER[$key], $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        // Cara 3: getallheaders() — fallback untuk Apache/PHP-FPM
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    if (preg_match('/Bearer\s+(.+)/i', $value, $matches)) {
                        return trim($matches[1]);
                    }
                }
            }
        }

        return null;
    }
}
