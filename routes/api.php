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

Route::post('/webhook/fingerprint', [WebhookController::class, 'handle']);

Route::post('/webhook/fingerprint/{uuid}', [WebhookController::class, 'handle'])
    ->middleware(['webhook.token', 'throttle:webhook'])
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