<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Logistic\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Logistic\GoodsInController;
use App\Http\Controllers\Logistic\GoodsOutController;
use App\Http\Controllers\Production\ProjectController;
use App\Http\Controllers\Production\ProjectStatusController;
use App\Http\Controllers\Production\JobOrderController;
use App\Http\Controllers\Production\JobOrderTypeGradingController;
use App\Http\Controllers\Logistic\CategoryController;
use App\Http\Controllers\Finance\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Logistic\InventoryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Logistic\MaterialUsageController;
use App\Http\Controllers\Finance\ProjectCostingController;
use App\Http\Controllers\Logistic\MaterialRequestController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Production\TimingController;
use App\Http\Controllers\Production\TimingApprovalController;
use App\Http\Controllers\Timing\Costume\CostumeTimingController;
use App\Http\Controllers\Timing\Costume\CostumeMonitorController;
use App\Http\Controllers\Timing\Animatronics\AnimatronicsTimingController;
use App\Http\Controllers\Timing\Animatronics\AnimatronicsMonitorController;
use App\Http\Controllers\Timing\Mascot\MascotTimingController;
use App\Http\Controllers\Timing\Mascot\MascotMonitorController;
use App\Http\Controllers\Timing\TimingMonitorController;
use App\Http\Controllers\Timing\TimingDetailController;
use App\Http\Controllers\Finance\FinalProjectSummaryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Procurement\SupplierController;
use App\Http\Controllers\Logistic\LocationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Procurement\PurchaseRequestController;
use App\Http\Controllers\Procurement\ShippingController;
use App\Http\Controllers\Procurement\ShippingManagementController;
use App\Http\Controllers\Procurement\GoodsReceiveController;
use App\Models\Procurement\Shippings;
use App\Http\Controllers\Procurement\PreShippingController;
use App\Models\Procurement\PreShipping;
use App\Http\Controllers\Hr\LeaveRequestController;
use App\Http\Controllers\Production\MaterialPlanningController;
use App\Http\Controllers\Hr\AttendanceController;
use App\Http\Controllers\Logistic\GoodsMovementController;
use App\Http\Controllers\Procurement\ShortageItemController;
use App\Http\Controllers\Procurement\ProjectPurchaseController;
use App\Http\Controllers\InternalProjectController;
use App\Http\Controllers\Finance\DcmCostingController;
use App\Http\Controllers\Finance\PurchaseApprovalController;
use App\Http\Controllers\Finance\PurchaseEditedController;
use App\Http\Controllers\Hr\EmployeeWorkPolicyController;
use App\Http\Controllers\Hr\AttendanceLogController;
use App\Http\Controllers\Hr\AttendanceSummaryController;
use App\Http\Controllers\Hr\EmployeeImportController;
use App\Http\Controllers\Hr\OvertimeRequestController;
use App\Http\Controllers\Hr\OvertimePayController;
use App\Http\Controllers\Hr\EmployeeWorkPolicyImportController;
use App\Http\Controllers\Hr\FingerprintLogController;
use App\Http\Controllers\Hr\FingerspotController;
use App\Http\Controllers\Hr\SessionShiftController;
use App\Http\Controllers\Hr\SymcoreExportController;
use App\Http\Controllers\ChatbotController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Leave Requests - Public access ONLY for create & store
Route::get('leave_requests/create', [LeaveRequestController::class, 'create'])->name('leave_requests.create');
Route::post('leave_requests', [LeaveRequestController::class, 'store'])->name('leave_requests.store');

// Kasbon - Public access (no login required)
Route::get('/pengajuan-kasbon', [\App\Http\Controllers\Finance\KasbonPublicController::class, 'create'])->name('kasbon.create');
Route::post('/pengajuan-kasbon', [\App\Http\Controllers\Finance\KasbonPublicController::class, 'store'])
    ->name('kasbon.store')
    ->middleware('throttle:3,1');
Route::get('/cek-kasbon', [\App\Http\Controllers\Finance\KasbonPublicController::class, 'status'])->name('kasbon.status');
// Employee leave balance - public agar guest bisa melihat sisa cuti saat form create
Route::get('/employees/{employee}/leave-balance', [EmployeeController::class, 'getLeaveBalance'])->name('employees.leave-balance.public');

Auth::routes([
    'reset' => false,
    'confirm' => false,
    'verify' => false,
]);

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Artisan Action Route
Route::get('/artisan/{action}', function ($action) {
    if (!Auth::check() || !Auth::user()->isSuperAdmin()) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
    }

    $allowedActions = [
        'clear-cache' => 'cache:clear',
        'config-clear' => 'config:clear',
        'config-cache' => 'config:cache',
        'optimize' => 'optimize',
        'optimize-clear' => 'optimize:clear',
    ];

    if (!isset($allowedActions[$action])) {
        return response()->json(['status' => 'error', 'message' => 'Invalid action'], 400);
    }

    try {
        Artisan::call($allowedActions[$action]);
        $output = Artisan::output();

        $messages = [
            'clear-cache' => 'Cache cleared successfully!',
            'config-clear' => 'Configuration cleared successfully!',
            'config-cache' => 'Configuration cache created successfully!',
            'optimize' => 'Application optimized successfully!',
            'optimize-clear' => 'Optimized cache cleared successfully!',
        ];

        return response()->json([
            'status' => 'success',
            'message' => $messages[$action],
            'output' => $output,
        ]);
    } catch (\Exception $e) {
        \Log::error("Artisan command error: {$action}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json(
            [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ],
            500,
        );
    }
})
    ->name('artisan.action.public')
    ->middleware('auth');

Route::middleware(['auth'])->group(function () {
    // Audit
    Route::prefix('audit')
        ->name('audit.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\AuditController::class, 'index'])->name('index');
            Route::get('/changes/{id}', [\App\Http\Controllers\AuditController::class, 'getChanges'])->name('getChanges');
            Route::delete('/{id}', [\App\Http\Controllers\AuditController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\AuditController::class, 'bulkDelete'])->name('bulkDelete');
            Route::post('/delete-by-date', [\App\Http\Controllers\AuditController::class, 'deleteByDateRange'])->name('deleteByDateRange');
            Route::post('/purge-old', [\App\Http\Controllers\AuditController::class, 'purgeOldLogs'])->name('purgeOldLogs');
        });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // HR Dashboard
    Route::get('/hr/dashboard', [\App\Http\Controllers\Hr\HrDashboardController::class, 'index'])->name('hr.dashboard');

    // Departments
    Route::resource('departments', DepartmentController::class);

    // National Holidays
    Route::resource('national-holidays', \App\Http\Controllers\Admin\NationalHolidayController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('auth');

    // Users
    Route::resource('users', UserController::class);

    // Role & Permission Management (super_admin only)
    Route::prefix('admin/roles')->name('admin.roles.')->middleware('can:admin.users.edit')->group(function () {
        Route::get('/',              [App\Http\Controllers\Admin\RoleController::class, 'index'])->name('index');
        Route::get('/{role}/edit',   [App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}',        [App\Http\Controllers\Admin\RoleController::class, 'update'])->name('update');
    });

    // Material Usage
    Route::resource('material_usage', MaterialUsageController::class);
    Route::get('/material-usage/export', [MaterialUsageController::class, 'export'])->name('material_usage.export');
    Route::get('/material-usage/get-by-inventory', [MaterialUsageController::class, 'getByInventory'])->name('material_usage.get_by_inventory');
    Route::get('/material-usage-bulk/create', [MaterialUsageController::class, 'bulkCreate'])->name('material_usage.bulk.create');
    Route::post('/material-usage-bulk/store', [MaterialUsageController::class, 'bulkStore'])->name('material_usage.bulk.store');
    Route::get('/material-usage/{materialUsage}/batch-usage', [MaterialUsageController::class, 'getBatchUsage'])->name('material_usage.batch_usage');

    // Inventory
    Route::get('/inventory/template', [InventoryController::class, 'downloadTemplate'])->name('inventory.template');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/sync-from-lark', [InventoryController::class, 'syncFromLark'])->name('inventory.sync.lark');
    Route::get('/inventory/lark-raw-data', [InventoryController::class, 'getLarkRawData'])->name('inventory.lark.raw');
    Route::get('/inventory/stock-value', [InventoryController::class, 'stockValue'])->name('inventory.stock-value');
    Route::resource('inventory', InventoryController::class);
    Route::post('/inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
    Route::get('/inventory/detail/{id}', [InventoryController::class, 'detail'])->name('inventory.detail');
    Route::post('/inventories/quick-add', [InventoryController::class, 'storeQuick'])->name('inventories.store.quick');
    Route::get('/inventories/json', [InventoryController::class, 'json'])->name('inventories.json');

    // Inventory Batches
    Route::get('/inventory-batch', [\App\Http\Controllers\Logistic\InventoryBatchController::class, 'index'])->name('inventory-batch.index');
    Route::get('/inventory-batch/stock-value', [\App\Http\Controllers\Logistic\InventoryBatchController::class, 'batchStockValue'])->name('inventory-batch.stock-value');
    Route::get('/inventory-batch/by-inventory/{id}', [\App\Http\Controllers\Logistic\InventoryBatchController::class, 'byInventory'])->name('inventory-batch.by-inventory');
    Route::get('/inventory-batch/fix-zero-price', [\App\Http\Controllers\Logistic\InventoryBatchController::class, 'fixZeroPriceIndex'])->name('inventory-batch.fix-zero-price');
    Route::post('/inventory-batch/fix-zero-price/{batch}', [\App\Http\Controllers\Logistic\InventoryBatchController::class, 'fixZeroPriceUpdate'])->name('inventory-batch.fix-zero-price.update');
    Route::get('/inventory-batch/fix-zero-price', [\App\Http\Controllers\Logistic\InventoryBatchController::class, 'fixZeroPriceIndex'])->name('inventory-batch.fix-zero-price');
    Route::post('/inventory-batch/fix-zero-price/{batch}', [\App\Http\Controllers\Logistic\InventoryBatchController::class, 'fixZeroPriceUpdate'])->name('inventory-batch.fix-zero-price.update');

    // Stock Adjustments
    Route::get('/stock-adjustments/batches', [\App\Http\Controllers\Logistic\StockAdjustmentController::class, 'getBatches'])->name('stock-adjustments.batches');
    Route::resource('stock-adjustments', \App\Http\Controllers\Logistic\StockAdjustmentController::class)->only(['index', 'create', 'store', 'show']);

    // Projects
    Route::get('/projects/export', [ProjectController::class, 'export'])->name('projects.export');
    Route::post('/projects/sync-from-lark', [ProjectController::class, 'syncFromLark'])->name('projects.sync.lark');
    Route::get('/projects/lark-raw-data', [ProjectController::class, 'getLarkRawData'])->name('projects.lark.raw');
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/quick-add', [ProjectController::class, 'storeQuick'])->name('projects.store.quick');
    Route::get('/projects/json', [ProjectController::class, 'json'])->name('projects.json');
    Route::post('/project-statuses', [ProjectStatusController::class, 'store'])->name('project-statuses.store');

    // Lark Staging Tables - View raw data from Lark before mapping to ERP
    Route::prefix('lark/staging')
        ->name('lark.staging.')
        ->group(function () {
            // BT-SG (Batam to Singapore)
            Route::get('/bt-sg-courier', [App\Http\Controllers\Lark\LarkStagingController::class, 'btSgCourierIndex'])->name('bt-sg-courier');
            Route::get('/bt-sg-items', [App\Http\Controllers\Lark\LarkStagingController::class, 'btSgItemIndex'])->name('bt-sg-items');
            Route::post('/sync-bt-sg-courier', [App\Http\Controllers\Lark\LarkStagingController::class, 'syncBtSgCourier'])->name('sync-bt-sg-courier');
            Route::post('/sync-bt-sg-items', [App\Http\Controllers\Lark\LarkStagingController::class, 'syncBtSgItems'])->name('sync-bt-sg-items');

            // SG-BT (Singapore to Batam)
            Route::get('/sg-bt-courier', [App\Http\Controllers\Lark\LarkStagingController::class, 'sgBtCourierIndex'])->name('sg-bt-courier');
            Route::get('/sg-bt-items', [App\Http\Controllers\Lark\LarkStagingController::class, 'sgBtItemIndex'])->name('sg-bt-items');
            Route::post('/sync-sg-bt-courier', [App\Http\Controllers\Lark\LarkStagingController::class, 'syncSgBtCourier'])->name('sync-sg-bt-courier');
            Route::post('/sync-sg-bt-items', [App\Http\Controllers\Lark\LarkStagingController::class, 'syncSgBtItems'])->name('sync-sg-bt-items');

            // Lark Staging Inventory (filter data purchase dari Lark sebelum masuk ke inventory listing)
            Route::get('/inventory', [App\Http\Controllers\Lark\LarkStagingController::class, 'inventoryIndex'])->name('inventory');
            Route::post('/sync-inventory', [App\Http\Controllers\Lark\LarkStagingController::class, 'syncInventory'])->name('sync-inventory');
            Route::post('/inventory/{id}/approve', [App\Http\Controllers\Lark\LarkStagingController::class, 'approveInventory'])->name('inventory.approve');
            Route::post('/inventory/{id}/reject', [App\Http\Controllers\Lark\LarkStagingController::class, 'rejectInventory'])->name('inventory.reject');
            Route::post('/inventory/{id}/reset', [App\Http\Controllers\Lark\LarkStagingController::class, 'resetInventory'])->name('inventory.reset');
            Route::post('/inventory/{id}/received-qty', [App\Http\Controllers\Lark\LarkStagingController::class, 'updateReceivedQty'])->name('inventory.update-received-qty');
            Route::post('/inventory/{id}/update-item', [App\Http\Controllers\Lark\LarkStagingController::class, 'updateItem'])->name('inventory.update-item');
            Route::post('/inventory/{id}/update-name', [App\Http\Controllers\Lark\LarkStagingController::class, 'updateName'])->name('inventory.update-name');
            Route::post('/inventory/{id}/update-unit', [App\Http\Controllers\Lark\LarkStagingController::class, 'updateUnit'])->name('inventory.update-unit');
            Route::post('/inventory/{id}/update-price', [App\Http\Controllers\Lark\LarkStagingController::class, 'updatePrice'])->name('inventory.update-price');
            Route::post('/inventory/bulk-approve', [App\Http\Controllers\Lark\LarkStagingController::class, 'bulkApproveInventory'])->name('inventory.bulk-approve');
        });

    // Job Orders
    Route::prefix('job-orders')
        ->name('job-orders.')
        ->group(function () {
            // Route spesifik harus di atas route dengan parameter {id}
            Route::post('/sync-from-lark', [JobOrderController::class, 'syncFromLark'])->name('sync.lark');
            Route::get('/lark-raw-data', [JobOrderController::class, 'getLarkRawData'])->name('lark.raw');
            Route::get('/export', [JobOrderController::class, 'export'])->name('export');
            Route::get('/template', [JobOrderController::class, 'downloadTemplate'])->name('template');
            Route::get('/create', [JobOrderController::class, 'create'])->name('create');
            Route::post('/import', [JobOrderController::class, 'import'])->name('import');

            // CRUD routes
            Route::get('/', [JobOrderController::class, 'index'])->name('index');
            Route::post('/', [JobOrderController::class, 'store'])->name('store');
            Route::get('/{id}', [JobOrderController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [JobOrderController::class, 'edit'])->name('edit');
            Route::put('/{id}', [JobOrderController::class, 'update'])->name('update');
            Route::delete('/{id}', [JobOrderController::class, 'destroy'])->name('destroy');

            // Soft delete functionality
            Route::put('/{id}/restore', [JobOrderController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [JobOrderController::class, 'forceDelete'])->name('force-delete');
        });

    // API untuk dropdown Job Orders
    Route::get('/api/job-orders/project/{projectId}', [JobOrderController::class, 'getByProject']);

    // Job Order Type Gradings
    Route::prefix('job-order-type-gradings')
        ->name('job-order-type-gradings.')
        ->group(function () {
            Route::get('/', [JobOrderTypeGradingController::class, 'index'])->name('index');
            Route::post('/sync-from-lark', [JobOrderTypeGradingController::class, 'syncFromLark'])->name('sync');
        });

    // Material Requests
    Route::get('/material_requests/export', [MaterialRequestController::class, 'export'])->name('material_requests.export');
    Route::get('/material_requests', [MaterialRequestController::class, 'index'])->name('material_requests.index');
    Route::get('/material_requests/create', [MaterialRequestController::class, 'create'])->name('material_requests.create');
    Route::post('/material_requests', [MaterialRequestController::class, 'store'])->name('material_requests.store');
    Route::get('/material_requests/bulk_create', [MaterialRequestController::class, 'bulkCreate'])->name('material_requests.bulk_create');
    Route::post('/material_requests/bulk_store', [MaterialRequestController::class, 'bulkStore'])->name('material_requests.bulk_store');
    Route::get('/material_requests/{id}/edit', [MaterialRequestController::class, 'edit'])->name('material_requests.edit');
    Route::put('/material_requests/{id}', [MaterialRequestController::class, 'update'])->name('material_requests.update');
    Route::delete('/material_requests/{id}', [MaterialRequestController::class, 'destroy'])->name('material_requests.destroy');
    Route::post('/material_requests/{id}/reminder', [MaterialRequestController::class, 'sendReminder'])->name('material_requests.reminder');
    Route::get('/material_requests/bulk_details', [MaterialRequestController::class, 'bulkDetails'])->name('material_requests.bulk_details');
    Route::post('/material_requests/bulk_details', [MaterialRequestController::class, 'bulkDetails'])->name('material_requests.bulk_details.post');
    Route::post('/material_requests/{id}/quick-update', [MaterialRequestController::class, 'quickUpdate'])->name('material_requests.quick_update');
    // AJAX: Unified incoming materials (lark_staging + indo_purchases) per Job Order
    Route::get('/material_requests/incoming-materials', [MaterialRequestController::class, 'getIncomingMaterials'])->name('material_requests.incoming_materials');

    // Material Planning
    Route::middleware(['auth'])->group(function () {
        Route::get('/material-planning', [MaterialPlanningController::class, 'index'])->name('material_planning.index');
        Route::get('/material-planning/create', [MaterialPlanningController::class, 'create'])->name('material_planning.create');
        Route::post('/material-planning', [MaterialPlanningController::class, 'store'])->name('material_planning.store');
        Route::delete('/material-planning/project/{projectId}', [MaterialPlanningController::class, 'destroyProject'])->name('material_planning.destroy_project');
        Route::delete('/material-planning/{id}', [MaterialPlanningController::class, 'destroy'])->name('material_planning.destroy');
        Route::get('/material-planning/related-items/{projectId}', [MaterialPlanningController::class, 'getRelatedItems'])
            ->name('material_planning.related_items')
            ->where('projectId', '[0-9]+');
    });

    // Categories
    Route::post('/categories/quick-add', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/json', [CategoryController::class, 'json'])->name('categories.json');

    // Units
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/units/json', [UnitController::class, 'json'])->name('units.json');

    // Suppliers
    Route::resource('suppliers', SupplierController::class);
    Route::post('/suppliers/quick-store', [SupplierController::class, 'quickStore'])->name('suppliers.quick_store');

    // Locations
    Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');

    // Currencies
    Route::resource('currencies', CurrencyController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::get('/currencies/{id}/edit', [CurrencyController::class, 'edit'])->name('currencies.edit');

    // Goods Out
    Route::get('/goods_out/export', [GoodsOutController::class, 'export'])->name('goods_out.export');
    Route::resource('goods_out', GoodsOutController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('/goods_out/create/{materialRequestId}', [GoodsOutController::class, 'create'])->name('goods_out.create_with_id');
    Route::get('/goods_out/create_independent', [GoodsOutController::class, 'createIndependent'])->name('goods_out.create_independent');
    Route::post('/goods_out/store_independent', [GoodsOutController::class, 'storeIndependent'])->name('goods_out.store_independent');
    Route::post('/material-requests/bulk-goods-out', [GoodsOutController::class, 'bulkGoodsOut'])->name('goods_out.bulk');
    Route::get('/goods_out/details', [GoodsOutController::class, 'getDetails'])->name('goods_out.details');
    Route::post('/goods-out/{id}/restore', [GoodsOutController::class, 'restore'])->name('goods_out.restore');
    Route::get('/goods-out/{id}/batch-usage', [GoodsOutController::class, 'getBatchUsage'])->name('goods_out.batch_usage');

    // Goods In
    Route::get('/goods_in/export', [GoodsInController::class, 'export'])->name('goods_in.export');
    Route::get('/goods_in', [GoodsInController::class, 'index'])->name('goods_in.index');
    Route::get('/goods_in/create', [GoodsInController::class, 'create'])->name('goods_in.create');
    Route::post('/goods_in', [GoodsInController::class, 'store'])->name('goods_in.store');
    Route::get('/goods_in/create/{goods_out_id}', [GoodsInController::class, 'create'])->name('goods_in.create_with_id');
    Route::get('/goods_in/create_independent', [GoodsInController::class, 'createIndependent'])->name('goods_in.create_independent');
    Route::post('/goods_in/store_independent', [GoodsInController::class, 'storeIndependent'])->name('goods_in.store_independent');
    Route::get('goods_in/{goods_in}/edit', [GoodsInController::class, 'edit'])->name('goods_in.edit');
    Route::put('goods_in/{goods_in}', [GoodsInController::class, 'update'])->name('goods_in.update');
    Route::delete('goods_in/{goods_in}', [GoodsInController::class, 'destroy'])->name('goods_in.destroy');
    Route::post('/goods-out/bulk-goods-in', [GoodsInController::class, 'bulkGoodsIn'])->name('goods_in.bulk');
    Route::post('/goods-in/{id}/restore', [GoodsInController::class, 'restore'])->name('goods_in.restore');

    // Costings
    Route::get('/costing-report', [ProjectCostingController::class, 'index'])->name('costing.report');
    Route::get('/costing-report/{project_id}/detail', [ProjectCostingController::class, 'showDetail'])->name('costing.detail');
    Route::get('/costing-report/{project_id}/detail/material', [ProjectCostingController::class, 'showMaterialDetail'])->name('costing.detail.material');
    Route::get('/costing-report/{project_id}/detail/workmanship', [ProjectCostingController::class, 'showWorkmanshipDetail'])->name('costing.detail.workmanship');
    Route::get('/costing-report/{project_id}/detail/freight', [ProjectCostingController::class, 'showFreightDetail'])->name('costing.detail.freight');
    Route::get('/costing-report/{project_id}', [ProjectCostingController::class, 'viewCosting'])->name('costing.view');
    Route::get('/costing-report/export/{project_id}', [ProjectCostingController::class, 'exportCosting'])->name('costing.export');
    Route::get('/costing-report-export-all', [ProjectCostingController::class, 'exportAllProjects'])->name('costing.export.all');
    Route::get('/costing-report/{project_id}/job-order/{job_order_id}/materials', [ProjectCostingController::class, 'getJobOrderMaterials'])->name('costing.job_order_materials');

    //set inventory
    Route::post('/set-inventory', function (Request $request) {
        $request->session()->put('inventory_id', $request->input('inventory_id'));
        return redirect()->back();
    })->name('set_inventory');

    // Trash
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/restore', [TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('/trash/force-delete', [TrashController::class, 'forceDelete'])->name('trash.forceDelete');
    Route::post('/trash/bulk-action', [TrashController::class, 'bulkAction'])->name('trash.bulkAction');
    Route::get('/trash/{id}/details', [TrashController::class, 'show'])->name('trash.show');
    Route::post('/trash/delete-by-date', [TrashController::class, 'deleteByDateRange'])->name('trash.deleteByDateRange');
    Route::post('/trash/purge-old', [TrashController::class, 'purgeOldTrash'])->name('trash.purgeOldTrash');

    // Employees — route statis harus SEBELUM resource agar tidak kalah dengan {employee}
    Route::get('/employees/search', [EmployeeController::class, 'search'])->name('employees.search');
    Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('/employees/near-expired', [EmployeeController::class, 'nearExpired'])->name('employees.near-expired');
    Route::post('/employees/check-employee-no', [EmployeeController::class, 'checkEmployeeNo'])->name('employees.check-employee-no');
    Route::post('/employees/check-ktp', [EmployeeController::class, 'checkKtpId'])->name('employees.check-ktp');
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{employee}/timing', [EmployeeController::class, 'timing'])->name('employees.timing');
    Route::delete('employee-documents/{document}', [EmployeeController::class, 'deleteDocument'])->name('employee-documents.destroy');
    Route::get('/employee-documents/{document}/download', [EmployeeController::class, 'downloadDocument'])->name('employee-documents.download');
    Route::get('/employees/{employee}/documents', [EmployeeController::class, 'getDocuments'])->name('employees.documents');

    // Employee Import Routes - TARUH DI SINI (HANYA SEKALI)
    Route::post('employees/import', [EmployeeImportController::class, 'import'])->name('employees.import');
    // Skillsets
    Route::post('/skillsets/store', [App\Http\Controllers\Hr\SkillsetController::class, 'store'])->name('skillsets.store');
    Route::get('/skillsets/json', [App\Http\Controllers\Hr\SkillsetController::class, 'json'])->name('skillsets.json');
    Route::get('/skillsets/search', [App\Http\Controllers\Hr\SkillsetController::class, 'search'])->name('skillsets.search');

    // Kasbon - Admin (authenticated)
    Route::prefix('admin/kasbon')
        ->name('kasbon.admin.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'index'])->name('index');
            Route::get('/installments', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'installments'])->name('installments');
            Route::get('/{id}', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'reject'])->name('reject');
            Route::post('/{id}/disburse', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'disburse'])->name('disburse');
            Route::post('/{id}/installments/{installmentId}/pay', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'payInstallment'])->name('installment.pay');
            Route::post('/{id}/installments/{installmentId}/confirm-pokok', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'confirmPokokRoute'])->name('installment.confirm-pokok');
            Route::post('/{id}/installments/{installmentId}/confirm-cash', [\App\Http\Controllers\Finance\KasbonAdminController::class, 'confirmCashRoute'])->name('installment.confirm-cash');
        });

    // Leave Request - Authenticated only
    Route::get('leave_requests', [LeaveRequestController::class, 'index'])->name('leave_requests.index');
    Route::get('leave_requests/{leave}/edit', [LeaveRequestController::class, 'edit'])->name('leave_requests.edit');
    Route::put('leave_requests/{leave}', [LeaveRequestController::class, 'update'])->name('leave_requests.update');
    Route::delete('leave_requests/{leave}', [LeaveRequestController::class, 'destroy'])->name('leave_requests.destroy');
    Route::get('leave_requests/{leave}', [LeaveRequestController::class, 'show'])->name('leave_requests.show');
    Route::get('leave_requests/{leave}/document/{type}', [LeaveRequestController::class, 'serveDocument'])->name('leave_requests.document');
    Route::post('leave_requests/{leave}/approval', [LeaveRequestController::class, 'updateApproval'])->name('leave_requests.updateApproval');
    Route::get('leave-approvals/dept', [LeaveRequestController::class, 'deptLeaveApprovals'])->name('leave_requests.dept-approvals');
    Route::get('leave-approvals/hr', [LeaveRequestController::class, 'hrLeaveApprovals'])->name('leave_requests.hr-approvals');
    Route::get('leave-approvals/director', [LeaveRequestController::class, 'directorLeaveApprovals'])->name('leave_requests.director-approvals');

    //Timming
    Route::resource('timings', TimingController::class);
    Route::post('timings/store-multiple', [TimingController::class, 'storeMultiple'])->name('timings.storeMultiple');
    Route::post('/timings/ajax-search', [TimingController::class, 'ajaxSearch'])->name('timings.ajax_search');
    Route::get('/timings-export', [TimingController::class, 'export'])->name('timings.export');
    Route::post('/timings-import', [TimingController::class, 'import'])->name('timings.import');
    Route::get('/timings-template', [TimingController::class, 'downloadTemplate'])->name('timings.template');

    // Timing Approval - Approve/Reject Timing Sessions
    Route::prefix('timing-approval')
        ->name('timing-approval.')
        ->group(function () {
            Route::get('/', [TimingApprovalController::class, 'index'])->name('index');
            Route::get('/{id}/edit', [TimingApprovalController::class, 'edit'])->name('edit');
            Route::put('/{id}', [TimingApprovalController::class, 'update'])->name('update');
            Route::post('/{id}/approve', [TimingApprovalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [TimingApprovalController::class, 'reject'])->name('reject');
            Route::post('/bulk-approve', [TimingApprovalController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('/bulk-reject', [TimingApprovalController::class, 'bulkReject'])->name('bulk-reject');
        });

    // Costume Timing - Costume Department Production Timer
    Route::prefix('costume-timing')
        ->name('costume-timing.')
        ->group(function () {
            Route::get('/', [CostumeTimingController::class, 'index'])->name('index');
            Route::post('/start', [CostumeTimingController::class, 'start'])->name('start');
            Route::post('/stop', [CostumeTimingController::class, 'stop'])->name('stop');
            Route::post('/freeze', [CostumeTimingController::class, 'freeze'])->name('freeze');
            Route::post('/unfreeze', [CostumeTimingController::class, 'unfreeze'])->name('unfreeze');
            Route::get('/active-sessions', [CostumeTimingController::class, 'getActiveSessions'])->name('active-sessions');
            Route::get('/available-employees', [CostumeTimingController::class, 'getAvailableEmployees'])->name('available-employees');
            Route::post('/get-sessions-info', [CostumeTimingController::class, 'getSessionsInfo'])->name('get-sessions-info');
            Route::get('/job-order/{jobOrderId}', [CostumeTimingController::class, 'getJobOrderInfo'])->name('job-order-info');
            // Costume Monitor
            Route::get('/monitor', [CostumeMonitorController::class, 'index'])->name('monitor');
            Route::get('/monitor/running', [CostumeMonitorController::class, 'getRunning'])->name('monitor.running');
            Route::get('/monitor/clocked-in', [CostumeMonitorController::class, 'getClockedIn'])->name('monitor.clocked-in');
            Route::post('/bulk-stop', [CostumeTimingController::class, 'bulkStop'])->name('bulk-stop');
        });

    // Animatronics Timing - Animatronics Department Production Timer
    Route::prefix('animatronics-timing')
        ->name('animatronics-timing.')
        ->group(function () {
            Route::get('/', [AnimatronicsTimingController::class, 'index'])->name('index');
            Route::post('/start', [AnimatronicsTimingController::class, 'start'])->name('start');
            Route::post('/stop', [AnimatronicsTimingController::class, 'stop'])->name('stop');
            Route::post('/pause', [AnimatronicsTimingController::class, 'pause'])->name('pause');
            Route::post('/freeze', [AnimatronicsTimingController::class, 'freeze'])->name('freeze');
            Route::post('/unfreeze', [AnimatronicsTimingController::class, 'unfreeze'])->name('unfreeze');
            Route::post('/quick-job-order', [AnimatronicsTimingController::class, 'quickStoreJobOrder'])->name('quick-job-order');
            Route::get('/active-sessions', [AnimatronicsTimingController::class, 'getActiveSessions'])->name('active-sessions');
            Route::get('/available-employees', [AnimatronicsTimingController::class, 'getAvailableEmployees'])->name('available-employees');
            // Animatronics Monitor
            Route::get('/monitor', [AnimatronicsMonitorController::class, 'index'])->name('monitor');
            Route::get('/monitor/running', [AnimatronicsMonitorController::class, 'getRunning'])->name('monitor.running');
            Route::get('/monitor/clocked-in', [AnimatronicsMonitorController::class, 'getClockedIn'])->name('monitor.clocked-in');
            Route::post('/bulk-stop', [AnimatronicsTimingController::class, 'bulkStop'])->name('bulk-stop');
        });

    // Mascot Timing - Mascot Department Production Timer with Stage Progress
    Route::prefix('mascot-timing')
        ->name('mascot-timing.')
        ->group(function () {
            Route::get('/', [MascotTimingController::class, 'index'])->name('index');
            Route::post('/start', [MascotTimingController::class, 'start'])->name('start');
            Route::post('/stop', [MascotTimingController::class, 'stop'])->name('stop');
            Route::post('/freeze', [MascotTimingController::class, 'freeze'])->name('freeze');
            Route::post('/unfreeze', [MascotTimingController::class, 'unfreeze'])->name('unfreeze');
            Route::get('/active-sessions', [MascotTimingController::class, 'getActiveSessions'])->name('active-sessions');
            Route::get('/available-employees', [MascotTimingController::class, 'getAvailableEmployees'])->name('available-employees');
            Route::get('/job-order/{jobOrderId}', [MascotTimingController::class, 'getJobOrderInfo'])->name('job-order-info');
            // Mascot Monitor
            Route::get('/monitor', [MascotMonitorController::class, 'index'])->name('monitor');
            Route::get('/monitor/running', [MascotMonitorController::class, 'getRunning'])->name('monitor.running');
            Route::get('/monitor/clocked-in', [MascotMonitorController::class, 'getClockedIn'])->name('monitor.clocked-in');
            Route::post('/bulk-stop', [MascotTimingController::class, 'bulkStop'])->name('bulk-stop');
        });

    // Timing Planner — plan employees per Job Order (admin mascot / admin costume)
    Route::prefix('timing-planner')
        ->name('timing-planner.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Timing\TimingPlannerController::class, 'index'])->name('index');
            Route::post('/save', [\App\Http\Controllers\Timing\TimingPlannerController::class, 'savePlan'])->name('save');
            Route::post('/clear', [\App\Http\Controllers\Timing\TimingPlannerController::class, 'clearPlan'])->name('clear');
            Route::get('/plan/{jobOrderId}', [\App\Http\Controllers\Timing\TimingPlannerController::class, 'getPlan'])->name('get');
        });

    // Timing Session Detail - Shared detail page for any timing session
    Route::get('/timing/heartbeat', [TimingDetailController::class, 'heartbeat'])->name('timing.heartbeat');
    Route::get('/timing/{id}/detail', [TimingDetailController::class, 'show'])->name('timing.detail.show');
    Route::get('/timing/{id}/detail-partial', [TimingDetailController::class, 'showPartial'])->name('timing.detail.partial');
    Route::get('/timing/{id}/live-stats', [TimingDetailController::class, 'liveStats'])->name('timing.detail.live-stats');

    // Timing Monitor - Real-time Running Sessions Dashboard (All Departments)
    Route::prefix('timing-monitor')
        ->name('timing-monitor.')
        ->group(function () {
            Route::get('/', [TimingMonitorController::class, 'index'])->name('index');
            Route::get('/running', [TimingMonitorController::class, 'getRunning'])->name('running');
            Route::get('/available-employees', [TimingMonitorController::class, 'getAvailableEmployees'])->name('available-employees');
            Route::post('/stop', [TimingMonitorController::class, 'stopSession'])->name('stop');
        });

    //Final Project Summary
    Route::get('final_project_summary', [FinalProjectSummaryController::class, 'index'])->name('final_project_summary.index');
    Route::get('final_project_summary/{project}', [FinalProjectSummaryController::class, 'show'])->name('final_project_summary.show');
    Route::get('/final-project-summary/ajax-search', [FinalProjectSummaryController::class, 'ajaxSearch'])->name('final_project_summary.ajax_search');

    // Purchase Requests
    Route::get('/purchase_requests/export', [PurchaseRequestController::class, 'export'])->name('purchase_requests.export');
    Route::post('/purchase_requests/bulk-handsontable', [PurchaseRequestController::class, 'bulkStoreHandsontable'])->name('purchase_requests.bulk_handsontable');
    Route::resource('purchase_requests', PurchaseRequestController::class)->middleware('auth');
    Route::post('/purchase_requests/{id}/quick-update', [PurchaseRequestController::class, 'quickUpdate'])->name('purchase_requests.quick_update');

    // Pre Shippings
    Route::get('/pre-shippings', [PreShippingController::class, 'index'])->name('pre-shippings.index');
    Route::post('/pre-shippings/{groupKey}/quick-update', [PreShippingController::class, 'quickUpdate'])->name('pre-shippings.quick-update');
    Route::get('/pre-shippings/check-orphaned-prs', [PreShippingController::class, 'checkOrphanedPRs'])->name('pre-shippings.check-orphaned-prs');

    // Shippings
    Route::get('/shippings/create', [ShippingController::class, 'create'])->name('shippings.create'); // ✅ CHANGE to GET
    Route::post('/shippings', [ShippingController::class, 'store'])->name('shippings.store'); // ✅ CORRECT - POST

    // Shipping Management
    Route::get('/shipping-management', [ShippingManagementController::class, 'index'])->name('shipping-management.index');
    Route::get('/shipping-management/detail/{id}', [ShippingManagementController::class, 'detail'])->name('shipping-management.detail');

    // Goods Receive
    Route::post('/goods-receive/store', [GoodsReceiveController::class, 'store'])->name('goods-receive.store');
    Route::get('/goods-receive', [GoodsReceiveController::class, 'index'])->name('goods-receive.index');

    // SHORTAGE ITEM
    Route::prefix('shortage-items')->group(function () {
        // Index/List shortage items
        Route::get('/', [ShortageItemController::class, 'index'])->name('shortage-items.index');

        // Show single shortage detail
        Route::get('/{id}', [ShortageItemController::class, 'show'])->name('shortage-items.show');

        // BULK RESEND ACTION (Main feature)
        Route::post('/bulk-resend', [ShortageItemController::class, 'bulkResend'])->name('shortage-items.bulk-resend');

        // Cancel shortage item
        Route::post('/{id}/cancel', [ShortageItemController::class, 'cancel'])->name('shortage-items.cancel');

        // Get by status (AJAX endpoint)
        Route::get('/status/filter', [ShortageItemController::class, 'getByStatus'])->name('shortage-items.by-status');
    });

    // Goods Movement
    Route::resource('goods-movement', GoodsMovementController::class);
    Route::post('goods-movement/{goods_movement}/update-status', [GoodsMovementController::class, 'updateStatus'])->name('goods-movement.updateStatus');
    Route::post('goods-movement/{goods_movement}/update-sender-receiver-status', [GoodsMovementController::class, 'updateSenderReceiverStatus'])->name('goods-movement.updateSenderReceiverStatus');
    Route::post('goods-movement-item/{itemId}/transfer-to-inventory', [GoodsMovementController::class, 'transferToInventory'])->name('goods-movement.transferToInventory');
    Route::post('goods-movement/parse-whatsapp', [GoodsMovementController::class, 'parseWhatsApp'])->name('goods-movement.parseWhatsApp');
    Route::get('goods-movement/export/csv', [GoodsMovementController::class, 'export'])->name('goods-movement.export');
    Route::get('goods-movement/api/movement-type-values', [GoodsMovementController::class, 'getMovementTypeValues'])->name('goods-movement.getMovementTypeValues');
    Route::get('goods-movement/api/projects', [GoodsMovementController::class, 'getProjects'])->name('goods-movement.getProjects');
    Route::get('goods-movement/api/goods-receives', [GoodsMovementController::class, 'getGoodsReceives'])->name('goods-movement.getGoodsReceives');
    Route::get('goods-movement/api/goods-receive-items', [GoodsMovementController::class, 'getGoodsReceiveItems'])->name('goods-movement.getGoodsReceiveItems');

    // Attendance
    Route::prefix('attendance')
        ->name('attendance.')
        ->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::post('/store', [AttendanceController::class, 'store'])->name('store');
            Route::post('/bulk-update', [AttendanceController::class, 'bulkUpdate'])->name('bulk-update');
            Route::post('/bulk-update-individual', [AttendanceController::class, 'bulkUpdateIndividual'])->name('bulk-update-individual');
            Route::post('/initialize', [AttendanceController::class, 'initializeDefault'])->name('initialize');

            // Attendance List/History
            Route::get('/list', [AttendanceController::class, 'list'])->name('list');
            Route::get('/export', [AttendanceController::class, 'exportList'])->name('export');
            Route::delete('/{id}', [AttendanceController::class, 'destroy'])->name('destroy');
        });

    // CRUD Routes for Project Purchases
    Route::prefix('project-purchases')->group(function () {
        Route::get('/', [ProjectPurchaseController::class, 'index'])->name('project-purchases.index');
        Route::get('/create', [ProjectPurchaseController::class, 'create'])->name('project-purchases.create');
        Route::get('/materials/search', [ProjectPurchaseController::class, 'searchMaterials'])->name('project-purchases.materials.search');
        Route::post('/', [ProjectPurchaseController::class, 'store'])->name('project-purchases.store');
        Route::get('/{uid}', [ProjectPurchaseController::class, 'show'])->name('project-purchases.show');
        Route::get('/{uid}/edit', [ProjectPurchaseController::class, 'edit'])->name('project-purchases.edit');
        Route::put('/{uid}', [ProjectPurchaseController::class, 'update'])->name('project-purchases.update');
        Route::delete('/{uid}', [ProjectPurchaseController::class, 'destroy'])->name('project-purchases.destroy');

        // Approval Routes (Finance)
        Route::post('/{uid}/approve', [ProjectPurchaseController::class, 'approve'])->name('project-purchases.approve');
        Route::post('/{uid}/reject', [ProjectPurchaseController::class, 'reject'])->name('project-purchases.reject');

        // Deletion Request Routes
        Route::post('/{uid}/request-deletion', [ProjectPurchaseController::class, 'requestDeletion'])->name('project-purchases.request-deletion');

        // Update Tracking Route
        Route::post('/{uid}/update-tracking', [ProjectPurchaseController::class, 'updateTracking'])->name('project-purchases.update-tracking');

        // Item Receipt Routes
        Route::post('/project-purchases/{uid}/mark-as-received', [ProjectPurchaseController::class, 'markAsReceived'])->name('project-purchases.mark-as-received');
        Route::post('/{uid}/mark-as-not-matched', [ProjectPurchaseController::class, 'markAsNotMatched'])->name('project-purchases.mark-as-not-matched');

        // Print & Export
        Route::get('/{uid}/print', [ProjectPurchaseController::class, 'print'])->name('project-purchases.print');
        Route::get('/export', [ProjectPurchaseController::class, 'export'])->name('project-purchases.export');

        // AJAX Routes
        Route::get('/material/{id}/price', [ProjectPurchaseController::class, 'getMaterialPrice'])->name('project-purchases.get-material-price');
        Route::get('/material/all', [ProjectPurchaseController::class, 'getMaterials'])->name('project-purchases.get-materials');
        Route::get('/po-items/{poNumber}', [ProjectPurchaseController::class, 'getPOItems'])->name('project-purchases.get-po-items');
        Route::get('/job-order/{id}/details', [ProjectPurchaseController::class, 'getJobOrderDetails'])->name('project-purchases.get-job-order-details');
    });

    Route::resource('internal-projects', InternalProjectController::class)->parameters(['internal-projects' => 'internalProject']);
    Route::get('project-purchases/internal-project/{id}', [ProjectPurchaseController::class, 'getInternalProjectDetails'])->name('project-purchases.internal-project-details');
    Route::post('/internal-projects/quick', [InternalProjectController::class, 'quickStore'])->name('internal_projects.quick');

    // DCM Costings Routes
    Route::prefix('dcm-costings')
        ->name('dcm-costings.')
        ->group(function () {
            Route::get('/', [DcmCostingController::class, 'index'])->name('index');
            Route::get('/export', [DcmCostingController::class, 'export'])->name('export');
            Route::get('/statistics', [DcmCostingController::class, 'statistics'])->name('statistics');
            Route::get('/create', [DcmCostingController::class, 'create'])->name('create');
            Route::post('/', [DcmCostingController::class, 'store'])->name('store');
            Route::get('/{costing:uid}', [DcmCostingController::class, 'show'])->name('show');
            Route::get('/{costing:uid}/edit', [DcmCostingController::class, 'edit'])->name('edit');
            Route::put('/{costing:uid}', [DcmCostingController::class, 'update'])->name('update');
            Route::delete('/{costing:uid}', [DcmCostingController::class, 'destroy'])->name('destroy');

            // New routes for edited purchases integration
            Route::get('/check-updates', [DcmCostingController::class, 'checkForUpdates'])->name('check-updates');
            Route::post('/{poNumber}/manual-update', [DcmCostingController::class, 'manualUpdate'])->name('manual-update');
            Route::get('/pending-updates', [DcmCostingController::class, 'getPendingUpdates'])->name('pending-updates');
            Route::post('/bulk-update', [DcmCostingController::class, 'bulkUpdate'])->name('bulk-update');
        });

    Route::prefix('purchase-approvals')
        ->name('purchase-approvals.')
        ->group(function () {
            Route::get('/', [PurchaseApprovalController::class, 'index'])->name('index');
            Route::get('/statistics', [PurchaseApprovalController::class, 'statistics'])->name('statistics');
            Route::get('/{id}/details', [PurchaseApprovalController::class, 'viewDetails'])->name('view-details');
            Route::post('/{id}/approve', [PurchaseApprovalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [PurchaseApprovalController::class, 'reject'])->name('reject');
            Route::post('/bulk-approve', [PurchaseApprovalController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('/bulk-reject', [PurchaseApprovalController::class, 'bulkReject'])->name('bulk-reject');
            Route::get('/deletion-requests', [PurchaseApprovalController::class, 'deletionRequests'])->name('deletion-requests');
            Route::get('/deleted-purchases', [PurchaseApprovalController::class, 'deletedPurchases'])->name('deleted-purchases');
            Route::get('/deletion-requests/{id}/detail', [PurchaseApprovalController::class, 'viewDeletionDetail'])->name('deletion-detail');
            Route::post('/{id}/approve-deletion', [PurchaseApprovalController::class, 'approveDeletion'])->name('approve-deletion');
            Route::post('/{id}/reject-deletion', [PurchaseApprovalController::class, 'rejectDeletion'])->name('reject-deletion');
        });
    Route::prefix('finance/purchase-edited')
        ->name('purchase-edited.')
        ->group(function () {
            Route::get('/', [PurchaseEditedController::class, 'index'])->name('index');
            Route::get('/compare/{poNumber}', [PurchaseEditedController::class, 'compare'])
                ->name('compare')
                ->where('poNumber', '.*');
            Route::post('/verify/{poNumber}', [PurchaseEditedController::class, 'verify'])
                ->name('verify')
                ->where('poNumber', '.*');
            Route::post('/bulk-verify', [PurchaseEditedController::class, 'bulkVerify'])->name('bulk-verify');
            Route::get('/check/{poNumber}', [PurchaseEditedController::class, 'check'])
                ->name('check')
                ->where('poNumber', '.*');
            Route::get('/count', [PurchaseEditedController::class, 'getCount'])->name('count');
        });
    Route::get('/finance-dashboard', function () {
        return redirect()->route('purchase-approvals.index');
    })->name('finance.dashboard');

    Route::get('/finance-costings', function () {
        return redirect()->route('dcm-costings.index');
    })->name('finance.costings');

    // Material Request Inventory Detail
    Route::get('/material-requests/inventory/{id}', [App\Http\Controllers\Logistic\MaterialRequestController::class, 'getInventoryDetail'])->name('material_requests.inventory_detail');
    Route::get('/material-requests/staging-inventories', [App\Http\Controllers\Logistic\MaterialRequestController::class, 'getStagingInventories'])->name('material_requests.staging_inventories');

    // Employee Work Policies
    Route::middleware(['auth'])->group(function () {
        Route::get('/employee-work-policies', [EmployeeWorkPolicyController::class, 'index'])->name('employee-work-policies.index');
        Route::get('/employee-work-policies/create', [EmployeeWorkPolicyController::class, 'create'])->name('employee-work-policies.create');
        Route::post('/employee-work-policies', [EmployeeWorkPolicyController::class, 'store'])->name('employee-work-policies.store');
        Route::get('/employee-work-policies/{policy}/edit', [EmployeeWorkPolicyController::class, 'edit'])->name('employee-work-policies.edit');
        Route::put('/employee-work-policies/{policy}', [EmployeeWorkPolicyController::class, 'update'])->name('employee-work-policies.update');
        Route::delete('/employee-work-policies/{policy}', [EmployeeWorkPolicyController::class, 'destroy'])->name('employee-work-policies.destroy');

        // Route untuk import (tambahkan ini)
        Route::post('/employee-work-policies/import', [EmployeeWorkPolicyController::class, 'storeImport'])->name('employee-work-policies.import');
    }); // API endpoint untuk mengambil jam kerja karyawan (opsional)
    Route::get('/employees/{employee}/work-hours', [App\Http\Controllers\Hr\EmployeeWorkPolicyController::class, 'getHours'])->name('employees.work-hours');

    // Fingerprint Webhook Logs (raw data dari mesin fingerprint)
    Route::get('/fingerprint-logs', [FingerprintLogController::class, 'index'])->name('fingerprint-logs.index');

    // Fingerspot Device Management
    Route::prefix('fingerspot')
        ->name('fingerspot.')
        ->group(function () {
            Route::get('/', [FingerspotController::class, 'index'])->name('index');

            // Halaman form (GET)
            Route::get('/sync', [FingerspotController::class, 'showSyncForm'])->name('sync.form');
            Route::get('/employee-list', [FingerspotController::class, 'showEmployeeList'])->name('employee-list.form');
            Route::get('/register-employee', [FingerspotController::class, 'showRegisterForm'])->name('register-employee.form');
            Route::get('/bulk-register', [FingerspotController::class, 'showBulkRegisterForm'])->name('bulk-register.form');
            Route::get('/register-biometric', [FingerspotController::class, 'showBiometricForm'])->name('register-biometric.form');
            Route::get('/delete-employee', [FingerspotController::class, 'showDeleteForm'])->name('delete-employee.form');
            Route::get('/device-info', [FingerspotController::class, 'showDeviceInfoForm'])->name('device-info.form');
            Route::get('/set-timezone', [FingerspotController::class, 'showTimezoneForm'])->name('set-timezone.form');
            Route::get('/restart', [FingerspotController::class, 'showRestartForm'])->name('restart.form');

            // Proses form (POST)
            Route::post('/sync', [FingerspotController::class, 'syncAttendance'])->name('sync');
            Route::post('/register-employee', [FingerspotController::class, 'registerEmployee'])->name('register-employee');
            Route::post('/bulk-register', [FingerspotController::class, 'bulkRegisterEmployees'])->name('bulk-register');
            Route::post('/register-biometric', [FingerspotController::class, 'registerBiometric'])->name('register-biometric');
            Route::post('/delete-employee', [FingerspotController::class, 'deleteEmployee'])->name('delete-employee');
            Route::post('/reset-device-status', [FingerspotController::class, 'resetDeviceStatus'])->name('reset-device-status');
            Route::post('/sync-device', [FingerspotController::class, 'syncFromDevice'])->name('sync-device');
            Route::post('/device-info', [FingerspotController::class, 'deviceInfo'])->name('device-info');
            Route::post('/set-timezone', [FingerspotController::class, 'setTimezone'])->name('set-timezone');
            Route::post('/restart', [FingerspotController::class, 'restartDevice'])->name('restart');

            // Download laporan absensi (XLSX)
            Route::get('/download-attendance', [FingerspotController::class, 'showDownloadForm'])->name('download-attendance.form');
            Route::post('/download-attendance', [FingerspotController::class, 'downloadAttendance'])->name('download-attendance');
        }); // Attendance Logs
    // Session Shifts CRUD
    Route::get('session-shifts/live-monitor', [SessionShiftController::class, 'liveMonitor'])->name('session-shifts.live-monitor');
    Route::resource('session-shifts', SessionShiftController::class)->except(['show']);
    Route::patch('session-shifts/{session_shift}/clear-break2', [SessionShiftController::class, 'clearBreak2'])->name('session-shifts.clear-break2');

    Route::get('/attendance-logs', [AttendanceLogController::class, 'index'])->name('attendance-logs.index');
    Route::get('/attendance-logs/summary', [AttendanceSummaryController::class, 'index'])->name('attendance-logs.summary');
    Route::get('/attendance-logs/summary/export', [AttendanceSummaryController::class, 'exportExcel'])->name('attendance-logs.summary.export');
    Route::post('/attendance-logs/company-holidays', [AttendanceSummaryController::class, 'storeHoliday'])->name('attendance-logs.company-holidays.store');
    Route::delete('/attendance-logs/company-holidays/{companyHoliday}', [AttendanceSummaryController::class, 'destroyHoliday'])->name('attendance-logs.company-holidays.destroy');
    Route::post('/attendance-logs/national-holidays', [AttendanceSummaryController::class, 'storeNationalHoliday'])->name('attendance-logs.national-holidays.store');
    Route::put('/attendance-logs/national-holidays/{nationalHoliday}', [AttendanceSummaryController::class, 'updateNationalHoliday'])->name('attendance-logs.national-holidays.update');
    Route::delete('/attendance-logs/national-holidays/{nationalHoliday}', [AttendanceSummaryController::class, 'destroyNationalHoliday'])->name('attendance-logs.national-holidays.destroy');
    Route::post('/attendance-logs/import', [AttendanceLogController::class, 'storeImport'])->name('attendance-logs.import.store');
    Route::get('/attendance-logs/import', function () {
        return redirect()->route('attendance-logs.index')->with('info', 'Halaman import telah dipindahkan. Gunakan tombol "Import Excel" di halaman ini.');
    })->name('attendance-logs.import.redirect');
    Route::get('/attendance-logs/export', [AttendanceLogController::class, 'export'])->name('attendance-logs.export');

    // Symcore Export
    Route::get('/symcore-export', [SymcoreExportController::class, 'index'])->name('symcore-export.index');
    Route::get('/symcore-export/download', [SymcoreExportController::class, 'export'])->name('symcore-export.export');
    Route::put('/{employeeId}/{date}', [AttendanceLogController::class, 'update'])->name('attendance-logs.update');

    // ===== ROUTES OVERTIME REQUESTS =====
    // Route spesifik harus sebelum resource
    Route::get('overtime-requests/attendance-comparison', [OvertimeRequestController::class, 'attendanceComparison'])->name('overtime-requests.attendance-comparison');
    Route::get('overtime-requests/hr/approvals', [OvertimeRequestController::class, 'hrApprovals'])->name('overtime-requests.hr-approvals');
    Route::get('overtime-requests/director/approvals', [OvertimeRequestController::class, 'directorApprovals'])->name('overtime-requests.director-approvals');

    Route::post('overtime-requests/{overtime_request}/submit', [OvertimeRequestController::class, 'submit'])->name('overtime-requests.submit');
    Route::post('overtime-requests/{overtime_request}/approve-hr', [OvertimeRequestController::class, 'approveHr'])->name('overtime-requests.approve-hr');
    Route::post('overtime-requests/{overtime_request}/approve-director', [OvertimeRequestController::class, 'approveDirector'])->name('overtime-requests.approve-director');
    Route::post('overtime-requests/{overtime_request}/toggle-pass', [OvertimeRequestController::class, 'togglePass'])->name('overtime-requests.toggle-pass');
    Route::post('overtime-requests/{overtime_request}/update-attendance', [OvertimeRequestController::class, 'updateAttendance'])->name('overtime-requests.update-attendance');
    Route::get('overtime-requests/{overtime_request}/calculate-pay', [OvertimeRequestController::class, 'calculatePay'])->name('overtime-requests.calculate-pay');

    // Resource route
    Route::resource('overtime-requests', OvertimeRequestController::class);

    // ===== ROUTES OVERTIME PAY =====
    Route::prefix('overtime-pays')
        ->name('overtime-pays.')
        ->group(function () {
            Route::get('/', [OvertimePayController::class, 'index'])->name('index');
            Route::get('/create', [OvertimePayController::class, 'create'])->name('create');
            Route::post('/', [OvertimePayController::class, 'store'])->name('store');
            Route::get('/{id}', [OvertimePayController::class, 'show'])->name('show');
            Route::delete('/{id}', [OvertimePayController::class, 'destroy'])->name('destroy');
        });

    // Efficiency Dashboard Routes
    Route::prefix('efficiency-dashboard')
        ->name('efficiency.')
        ->middleware('auth')
        ->group(function () {
            Route::get('/', [App\Http\Controllers\Production\EfficiencyDashboardController::class, 'index'])->name('index');
            Route::get('/project/{project}', [App\Http\Controllers\Production\EfficiencyDashboardController::class, 'projectDetail'])->name('project.detail');
            Route::get('/job-order/{jobOrder}', [App\Http\Controllers\Production\EfficiencyDashboardController::class, 'jobOrderDetail'])->name('job-order.detail');
            Route::get('/project/{project}/export', [App\Http\Controllers\Production\EfficiencyDashboardController::class, 'exportProject'])->name('project.export');
            Route::get('/job-order/{jobOrder}/export', [App\Http\Controllers\Production\EfficiencyDashboardController::class, 'exportJobOrder'])->name('job-order.export');
        });

    // Employee Performance & Ranking Routes
    Route::prefix('performanceEmployee')
        ->name('performanceEmployee.')
        ->middleware('auth')
        ->group(function () {
            Route::get('/', [App\Http\Controllers\Production\EmployeePerformanceController::class, 'index'])->name('index');
            Route::get('/export/rankings', [App\Http\Controllers\Production\EmployeePerformanceController::class, 'export'])->name('export');
        });

    // API Routes for Employee Performance
    Route::prefix('api/performanceEmployee')
        ->middleware('auth')
        ->group(function () {
            Route::get('/{employee}/score', [App\Http\Controllers\Production\EmployeePerformanceController::class, 'getPerformanceScore']);
        });

    // Chatbot AI
    Route::post('/chatbot/message', [\App\Http\Controllers\ChatbotController::class, 'message'])->name('chatbot.message');

    // Groq API connectivity test (local dev only)
    Route::get('/test-groq', function () {
        if (!app()->environment('local')) {
            abort(404);
        }
        $apiKey = config('services.groq.api_key');
        $url = config('services.groq.url');
        $model = config('services.groq.model');

        $result = ['api_key_set' => !blank($apiKey), 'url' => $url, 'model' => $model];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->withOptions(['verify' => false])
                ->post($url, [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => 'Reply with just: OK']],
                    'max_tokens' => 10,
                ]);

            $result['http_status'] = $response->status();
            $result['success'] = $response->successful();
            $result['body'] = $response->json();
        } catch (\Exception $e) {
            $result['exception'] = $e->getMessage();
        }

        return response()->json($result, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    });

    // Feature Announcements Routes
    Route::prefix('feature-announcements')
        ->name('feature-announcements.')
        ->middleware('auth')
        ->group(function () {
            // Admin routes
            Route::get('/', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'destroy'])->name('destroy');

            // User API endpoints
            Route::get('/user', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'getUserAnnouncements'])->name('user');
            Route::post('/{id}/mark-read', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'markAsRead'])->name('mark-read');
            Route::post('/{id}/re-broadcast', [App\Http\Controllers\Admin\FeatureAnnouncementController::class, 'reBroadcast'])->name('re-broadcast');
        });

    // ─── Warning Letter Module ─────────────────────────────────────────────────
    Route::prefix('warning-letters')->name('warning-letters.')->middleware('auth')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Hr\WarningLetterController::class, 'dashboard'])->name('dashboard');
        Route::get('/',           [App\Http\Controllers\Hr\WarningLetterController::class, 'index'])->name('index');
        Route::get('/create',     [App\Http\Controllers\Hr\WarningLetterController::class, 'create'])->name('create');
        Route::post('/',          [App\Http\Controllers\Hr\WarningLetterController::class, 'store'])->name('store');
        Route::get('/{warningLetter}',        [App\Http\Controllers\Hr\WarningLetterController::class, 'show'])->name('show');
        Route::get('/{warningLetter}/edit',   [App\Http\Controllers\Hr\WarningLetterController::class, 'edit'])->name('edit');
        Route::put('/{warningLetter}',        [App\Http\Controllers\Hr\WarningLetterController::class, 'update'])->name('update');
        Route::delete('/{warningLetter}',     [App\Http\Controllers\Hr\WarningLetterController::class, 'destroy'])->name('destroy');
        Route::post('/{warningLetter}/approve',            [App\Http\Controllers\Hr\WarningLetterController::class, 'approve'])->name('approve');
        Route::post('/{warningLetter}/acknowledge',        [App\Http\Controllers\Hr\WarningLetterController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{warningLetter}/terminate-employee', [App\Http\Controllers\Hr\WarningLetterController::class, 'terminateEmployee'])->name('terminate-employee');
        Route::get('/{warningLetter}/pdf',                 [App\Http\Controllers\Hr\WarningLetterController::class, 'pdf'])->name('pdf');
    });

    // ─── Warning Batches (Bulk) ────────────────────────────────────────────────
    Route::prefix('warning-batches')
        ->name('warning-batches.')
        ->middleware('auth')
        ->group(function () {
            Route::get('/', [App\Http\Controllers\Hr\WarningBatchController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Hr\WarningBatchController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Hr\WarningBatchController::class, 'store'])->name('store');
            Route::get('/{warningBatch}', [App\Http\Controllers\Hr\WarningBatchController::class, 'show'])->name('show');
        });

    // ─── Violation Categories (Master Data) ───────────────────────────────────
    Route::resource('violation-categories', App\Http\Controllers\Hr\ViolationCategoryController::class)->middleware('auth');
});
