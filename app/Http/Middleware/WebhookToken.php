<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookToken
{
    public function handle(Request $request, Closure $next)
    {
        $validUuid = config('services.webhook.uuid');

        // Validasi UUID di path — UUID yang panjang & acak cukup sebagai secret
        // karena fingerspot.io tidak mendukung custom Authorization header
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