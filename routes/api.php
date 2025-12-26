<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\AuthController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// Protected routes (require API token)
Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::get('/projects', [ProjectApiController::class, 'getProjects']);
    Route::get('/projects/{id}', [ProjectApiController::class, 'getProjectById']);
    Route::get('/parts', [ProjectApiController::class, 'getPartsByProject']);
    Route::get('/employees', [ProjectApiController::class, 'getEmployees']);
});

// Health check endpoint (no auth)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()->toIso8601String(),
    ]);
});