<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Qc\QcDashboardController;
use App\Http\Controllers\Qc\QcProjectController;
use App\Http\Controllers\Qc\QcChecklistController;
use App\Http\Controllers\Qc\QcRejectLogController;
use App\Http\Controllers\Qc\QcPackingController;
use App\Http\Controllers\Qc\QcDailyProgressController;
use App\Http\Controllers\Qc\QcPhotoController;
use App\Http\Controllers\Qc\QcStageProductionController;

Route::middleware(['auth'])->prefix('qc/api')->name('qc.api.')->group(function () {

    // Dashboard overview stats
    Route::get('/dashboard', [QcDashboardController::class, 'index'])->name('dashboard');

    // Job orders available untuk dipilih saat buat project baru
    Route::get('/job-orders/available', [QcProjectController::class, 'availableJobOrders'])->name('job-orders.available');

    // Employees DCM Mascot (for operator assignment)
    Route::get('/employees', [QcProjectController::class, 'employees'])->name('employees');

    // QC Projects CRUD
    Route::get('/projects', [QcProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [QcProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project:uid}', [QcProjectController::class, 'show'])->name('projects.show');
    Route::delete('/projects/{project:uid}', [QcProjectController::class, 'destroy'])->name('projects.destroy');

    // Checklist
    Route::put('/projects/{project:uid}/checklist/{itemId}', [QcChecklistController::class, 'update'])->name('checklist.update');

    // Reject Logs
    Route::get('/projects/{project:uid}/reject-logs', [QcRejectLogController::class, 'index'])->name('reject-logs.index');
    Route::post('/projects/{project:uid}/reject-logs', [QcRejectLogController::class, 'store'])->name('reject-logs.store');
    Route::put('/reject-logs/{log:uid}', [QcRejectLogController::class, 'update'])->name('reject-logs.update');

    // Packing
    Route::put('/projects/{project:uid}/packing/{item:uid}', [QcPackingController::class, 'update'])->name('packing.update');
    Route::post('/projects/{project:uid}/packing/custom', [QcPackingController::class, 'addCustom'])->name('packing.custom');
    Route::delete('/projects/{project:uid}/packing/{item:uid}', [QcPackingController::class, 'destroy'])->name('packing.destroy');
    Route::post('/projects/{project:uid}/packing/verify', [QcPackingController::class, 'verify'])->name('packing.verify');

    // Daily Progress
    Route::get('/projects/{project:uid}/daily/{date}', [QcDailyProgressController::class, 'show'])->name('daily.show');
    Route::put('/projects/{project:uid}/daily/{date}', [QcDailyProgressController::class, 'upsert'])->name('daily.upsert');
    Route::put('/projects/{project:uid}/daily/{date}/items/{itemId}', [QcDailyProgressController::class, 'updateItem'])->name('daily.items.update');
    Route::post('/projects/{project:uid}/daily/{date}/items/{itemId}/finalize', [QcDailyProgressController::class, 'finalizeItem'])->name('daily.items.finalize');

    // Photos (upload & delete)
    Route::post('/photos', [QcPhotoController::class, 'store'])->name('photos.store');
    Route::delete('/photos/{photo:uid}', [QcPhotoController::class, 'destroy'])->name('photos.destroy');

    // Final decision
    Route::post('/projects/{project:uid}/final-decision', [QcProjectController::class, 'finalDecision'])->name('projects.final-decision');

    // Custom parts (for Daily Progress part picker)
    Route::patch('/projects/{project:uid}/custom-parts', [QcProjectController::class, 'addCustomPart'])->name('projects.custom-parts');

    // Stage Production (cutting / sewing / finishing)
    Route::prefix('/projects/{project:uid}/stages/{stage}')->name('stage.')->group(function () {
        Route::get('/records',                         [QcStageProductionController::class, 'records'])->name('records');
        Route::post('/records',                        [QcStageProductionController::class, 'store'])->name('records.store');
        Route::put('/records/{itemUid}',               [QcStageProductionController::class, 'update'])->name('records.update');
        Route::post('/records/{itemUid}/inspect',      [QcStageProductionController::class, 'inspect'])->name('records.inspect');
        Route::get('/reject-logs',                     [QcStageProductionController::class, 'rejectLogs'])->name('reject-logs');
        Route::post('/reject-logs',                    [QcStageProductionController::class, 'storeRejectLog'])->name('reject-logs.store');
        Route::post('/reject-logs/batch',              [QcStageProductionController::class, 'batchStoreRejectLogs'])->name('reject-logs.batch');
        Route::put('/reject-logs/{logUid}',            [QcStageProductionController::class, 'updateRejectLog'])->name('reject-logs.update');
        Route::get('/gallery',                         [QcStageProductionController::class, 'gallery'])->name('gallery');
        Route::get('/history',                         [QcStageProductionController::class, 'history'])->name('history');
    });
});
