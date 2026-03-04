<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookToken
{
    public function handle(Request $request, Closure $next)
    {
        // Gunakan config() bukan env() agar aman saat config:cache & route:cache
        $validToken = config('services.webhook.token');
        $validUuid  = config('services.webhook.uuid');

        // Validasi token dari Authorization: Bearer header
        $token = $request->bearerToken();

        if (!$token) {
            Log::warning('Webhook: No token provided', ['ip' => $request->ip()]);

            return response()->json([
                'success' => false,
                'message' => 'Token is required',
            ], 401);
        }

        if (!hash_equals((string) $validToken, (string) $token)) {
            Log::warning('Webhook: Invalid token', [
                'ip'             => $request->ip(),
                'token_provided' => substr($token, 0, 10) . '...',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }

        // Validasi UUID di path agar cocok dengan yang dikonfigurasi di .env
        $uuidFromPath = $request->route('uuid');
        if ($validUuid && $uuidFromPath !== $validUuid) {
            Log::warning('Webhook: UUID mismatch', [
                'ip'            => $request->ip(),
                'uuid_received' => $uuidFromPath,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid endpoint',
            ], 404);
        }

        return $next($request);
    }
}