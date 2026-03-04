<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|   
*/

// Route untuk user authentication (sanctum)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ===== API INTERNAL v1 — dilindungi api.token =====
Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::get('/projects',        [ProjectApiController::class, 'getProjects']);
    Route::get('/projects/{uid}',  [ProjectApiController::class, 'getProjectById']);
    Route::get('/parts',           [ProjectApiController::class, 'getPartsByProject']);
    Route::get('/employees',       [ProjectApiController::class, 'getEmployees']);
});

// ===== WEBHOOK FINGERPRINT =====
// Layer 1 — webhook.token  (WebhookToken)      : validasi Bearer token + UUID di path
// Layer 2 — webhook.hmac   (VerifyWebhookHMAC) : validasi HMAC-SHA256 timestamp + body
// Layer 3 — throttle:webhook                   : rate limiting 60 req/menit per IP (log jika terlampaui)
Route::post('/webhook/fingerprint/{uuid}', [WebhookController::class, 'handle'])
    ->middleware(['webhook.token', 'webhook.hmac', 'throttle:webhook'])
    ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

// Health check endpoint (public - no auth)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
        'php_version' => phpversion(),
    ]);
});

// ===== TESTING ENDPOINTS (HAPUS SAAT PRODUCTION) =====
/*
Route::get('/debug/headers', function (\Illuminate\Http\Request $request) {
    if (!app()->environment('local', 'development')) {
        return response()->json(['error' => 'Not available in production'], 404);
    }
    return response()->json([
        'bearer_token'             => $request->bearerToken(),
        'authorization_header'     => $request->header('Authorization'),
        'all_headers'              => $request->headers->all(),
        'server_http_authorization'=> $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        'server_redirect_http_auth'=> $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        'getallheaders'            => function_exists('getallheaders') ? getallheaders() : 'not available',
        'php_sapi'                 => PHP_SAPI,
        'config_token_set'         => !empty(config('services.api.token')),
    ]);
});
*/

// Endpoint untuk cek konfigurasi webhook - TANPA AUTH
Route::get('/debug/webhook-config', function () {
    // Hanya tampilkan di environment local/debug
    if (app()->environment('local', 'development')) {
        return response()->json([
            'webhook_url' => url('/api/webhook/fingerprint/' . env('WEBHOOK_UUID')),
            'webhook_uuid' => env('WEBHOOK_UUID'),
            'webhook_token_exists' => !empty(env('WEBHOOK_TOKEN')),
            'warning' => 'Endpoint ini hanya untuk debugging! HAPUS di production!'
        ]);
    }
    
    return response()->json(['error' => 'Not available in production'], 404);
})->middleware('webhook.token'); // Tetap pakai token untuk keamanan

// Simple test endpoint tanpa auth (untuk cek koneksi)
Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'pong',
        'timestamp' => now()->toIso8601String()
    ]);
});