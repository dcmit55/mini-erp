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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Leave Requests - Public access untuk create & index
Route::get('leave_requests', [LeaveRequestController::class, 'index'])->name('leave_requests.index');
Route::get('leave_requests/create', [LeaveRequestController::class, 'create'])->name('leave_requests.create');
Route::post('leave_requests', [LeaveRequestController::class, 'store'])->name('leave_requests.store');

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

    // Users
    Route::resource('users', UserController::class);

    // Material Usage
    Route::resource('material_usage', MaterialUsageController::class);
    Route::get('/material-usage/export', [MaterialUsageController::class, 'export'])->name('material_usage.export');
    Route::get('/material-usage/get-by-inventory', [MaterialUsageController::class, 'getByInventory'])->name('material_usage.get_by_inventory');

    // Inventory
    Route::get('/inventory/template', [InventoryController::class, 'downloadTemplate'])->name('inventory.template');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::resource('inventory', InventoryController::class);
    Route::post('/inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
    Route::get('/inventory/detail/{id}', [InventoryController::class, 'detail'])->name('inventory.detail');
    Route::post('/inventories/quick-add', [InventoryController::class, 'storeQuick'])->name('inventories.store.quick');
    Route::get('/inventories/json', [InventoryController::class, 'json'])->name('inventories.json');

    // Projects
    Route::get('/projects/export', [ProjectController::class, 'export'])->name('projects.export');
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/quick-add', [ProjectController::class, 'storeQuick'])->name('projects.store.quick');
    Route::get('/projects/json', [ProjectController::class, 'json'])->name('projects.json');
    Route::post('/project-statuses', [ProjectStatusController::class, 'store'])->name('project-statuses.store');

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
    Route::get('material_requests/bulk_details', [MaterialRequestController::class, 'bulkDetails'])->name('material_requests.bulk_details');
    Route::post('/material_requests/{id}/quick-update', [MaterialRequestController::class, 'quickUpdate'])->name('material_requests.quick_update');

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

    // Departments
    Route::post('/departments/store', [DepartmentController::class, 'store'])->name('departments.store');

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
    Route::get('/costing-report/{project_id}', [ProjectCostingController::class, 'viewCosting'])->name('costing.view');
    Route::get('/costing-report/export/{project_id}', [ProjectCostingController::class, 'exportCosting'])->name('costing.export');

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

    // Employees
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{employee}/timing', [EmployeeController::class, 'timing'])->name('employees.timing');
    Route::delete('employee-documents/{document}', [EmployeeController::class, 'deleteDocument'])->name('employee-documents.destroy');
    Route::post('/employees/check-employee-no', [EmployeeController::class, 'checkEmployeeNo'])->name('employees.check-employee-no');
    Route::post('/employees/check-ktp', [EmployeeController::class, 'checkKtpId'])->name('employees.check-ktp');
    Route::get('/employee-documents/{document}/download', [EmployeeController::class, 'downloadDocument'])->name('employee-documents.download');
    Route::get('/employees/{employee}/documents', [EmployeeController::class, 'getDocuments'])->name('employees.documents');

    // Skillsets
    Route::post('/skillsets/store', [App\Http\Controllers\Hr\SkillsetController::class, 'store'])->name('skillsets.store');
    Route::get('/skillsets/json', [App\Http\Controllers\Hr\SkillsetController::class, 'json'])->name('skillsets.json');
    Route::get('/skillsets/search', [App\Http\Controllers\Hr\SkillsetController::class, 'search'])->name('skillsets.search');

    // Employee leave balance check - Authenticated only
    Route::get('/employees/{employee}/leave-balance', [EmployeeController::class, 'getLeaveBalance'])->name('employees.leave-balance');

    // Leave Request - Authenticated only
    Route::get('leave_requests/{id}/edit', [LeaveRequestController::class, 'edit'])->name('leave_requests.edit');
    Route::put('leave_requests/{id}', [LeaveRequestController::class, 'update'])->name('leave_requests.update');
    Route::delete('leave_requests/{id}', [LeaveRequestController::class, 'destroy'])->name('leave_requests.destroy');
    Route::post('leave_requests/{id}/approval', [LeaveRequestController::class, 'updateApproval'])->name('leave_requests.updateApproval');

    //Timming
    Route::resource('timings', TimingController::class);
    Route::post('timings/store-multiple', [TimingController::class, 'storeMultiple'])->name('timings.storeMultiple');
    Route::post('/timings/ajax-search', [TimingController::class, 'ajaxSearch'])->name('timings.ajax_search');
    Route::get('/timings-export', [TimingController::class, 'export'])->name('timings.export');
    Route::post('/timings-import', [TimingController::class, 'import'])->name('timings.import');
    Route::get('/timings-template', [TimingController::class, 'downloadTemplate'])->name('timings.template');

    //Final Project Summary
    Route::get('final_project_summary', [FinalProjectSummaryController::class, 'index'])->name('final_project_summary.index');
    Route::get('final_project_summary/{project}', [FinalProjectSummaryController::class, 'show'])->name('final_project_summary.show');
    Route::get('/final-project-summary/ajax-search', [FinalProjectSummaryController::class, 'ajaxSearch'])->name('final_project_summary.ajax_search');

    // Purchase Requests
    Route::get('/purchase_requests/export', [PurchaseRequestController::class, 'export'])->name('purchase_requests.export');
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
});

Route::get('/artisan/{action}', function ($action) {
    try {
        // Check if user is super_admin
        if (auth()->user()->role !== 'super_admin') {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'You do not have the required permissions to perform this operation. This action is restricted to system administrators.',
                ],
                403,
            );
        }

        // Normalize action name - convert hyphen to colon untuk Lark command
        $actionMap = [
            'storage-link' => 'storage:link',
            'clear-cache' => 'cache:clear',
            'config-clear' => 'config:clear',
            'config-cache' => 'config:cache',
            'route-clear' => 'route:clear',
            'route-cache' => 'route:cache',
            'view-cache' => 'view:clear',
            'optimize' => 'optimize',
            'optimize-clear' => 'optimize:clear',
            'lark-fetch-job-orders' => 'lark:fetch-job-orders', // Convert hyphen to colon
        ];

        if (!isset($actionMap[$action])) {
            throw new Exception("Invalid action: {$action}");
        }

        $command = $actionMap[$action];
        Artisan::call($command);

        // Generate success message
        $messages = [
            'storage:link' => 'Storage link created successfully.',
            'cache:clear' => 'Cache cleared successfully.',
            'config:clear' => 'Configuration cleared successfully.',
            'config:cache' => 'Configuration cache cleared successfully.',
            'route:clear' => 'Route cache cleared successfully.',
            'route:cache' => 'Route cache created successfully.',
            'view:clear' => 'View cache cleared successfully.',
            'optimize' => 'Application optimized successfully.',
            'optimize:clear' => 'Application optimized and cache cleared successfully.',
            'lark:fetch-job-orders' => 'Job orders fetched from Lark successfully.',
        ];

        $message = $messages[$command] ?? 'Command executed successfully.';

        return response()->json(['status' => 'success', 'message' => $message]);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
})->name('artisan.action');
