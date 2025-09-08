<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\GoodsInController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\GoodsOutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MaterialUsageController;
use App\Http\Controllers\ProjectCostingController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TimingController;
use App\Http\Controllers\FinalProjectSummaryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ExternalRequestController;

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
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', UserController::class);

    // Material Usage
    Route::get('/material-usage/export', [MaterialUsageController::class, 'export'])->name('material_usage.export');
    Route::get('/material-usage', [MaterialUsageController::class, 'index'])->name('material_usage.index');
    Route::delete('material-usage/{material_usage}', [MaterialUsageController::class, 'destroy'])->name('material_usage.destroy');
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

    // Categories
    Route::post('/categories/quick-add', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/json', [CategoryController::class, 'json'])->name('categories.json');

    // Units
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/units/json', [UnitController::class, 'json'])->name('units.json');

    // Suppliers
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');

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

    // Employees
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{employee}/timing', [EmployeeController::class, 'timing'])->name('employees.timing');
    Route::delete('employee-documents/{document}', [EmployeeController::class, 'deleteDocument'])->name('employee-documents.destroy');
    Route::post('/employees/check-employee-no', [EmployeeController::class, 'checkEmployeeNo'])->name('employees.check-employee-no');
    Route::get('/employee-documents/{document}/download', [EmployeeController::class, 'downloadDocument'])->name('employee-documents.download');
    Route::get('/employees/{employee}/documents', [EmployeeController::class, 'getDocuments'])->name('employees.documents');

    //Timming
    Route::resource('timings', TimingController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('timings/store-multiple', [TimingController::class, 'storeMultiple'])->name('timings.storeMultiple');
    Route::post('/timings/ajax-search', [TimingController::class, 'ajaxSearch'])->name('timings.ajax_search');

    //Final Project Summary
    Route::get('final_project_summary', [FinalProjectSummaryController::class, 'index'])->name('final_project_summary.index');
    Route::get('final_project_summary/{project}', [FinalProjectSummaryController::class, 'show'])->name('final_project_summary.show');
    Route::get('/final-project-summary/ajax-search', [FinalProjectSummaryController::class, 'ajaxSearch'])->name('final_project_summary.ajax_search');

    // External Requests
    Route::get('/external_requests/export', [ExternalRequestController::class, 'export'])->name('external_requests.export');
    Route::resource('external_requests', ExternalRequestController::class)->middleware('auth');
});

Route::get('/artisan/{action}', function ($action) {
    try {
        switch ($action) {
            case 'storage-link':
                Artisan::call('storage:link');
                $message = 'Storage link created successfully.';
                break;
            case 'clear-cache':
                Artisan::call('cache:clear');
                $message = 'Cache cleared successfully.';
                break;
            case 'config-clear':
                Artisan::call('config:clear');
                $message = 'Configuration cleared successfully.';
                break;
            case 'config-cache':
                Artisan::call('config:cache');
                $message = 'Configuration cache cleared successfully.';
                break;
            case 'route-clear':
                Artisan::call('route:clear');
                $message = 'Route cache cleared successfully.';
                break;
            case 'route-cache':
                Artisan::call('route:cache');
                $message = 'Route cache created successfully.';
                break;
            case 'view-cache':
                Artisan::call('view:clear');
                $message = 'View cache cleared successfully.';
                break;
            case 'optimize':
                Artisan::call('optimize');
                $message = 'Application optimized successfully.';
                break;
            case 'optimize-clear':
                Artisan::call('optimize:clear');
                $message = 'Application optimized and cache cleared successfully.';
                break;
            default:
                throw new Exception('Invalid action.');
        }
        return response()->json(['status' => 'success', 'message' => $message]);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
})->name('artisan.action');

// Demo routes for testing error pages
if (app()->environment('local')) {
    Route::prefix('demo/errors')->group(function () {
        Route::get('/', function () {
            return view('demo.error-test');
        })->name('demo.errors');

        Route::get('/400', function () {
            abort(400);
        })->name('demo.400');

        Route::get('/401', function () {
            abort(401);
        })->name('demo.401');

        Route::get('/403', function () {
            abort(403);
        })->name('demo.403');

        Route::get('/404', function () {
            abort(404);
        })->name('demo.404');

        Route::get('/405', function () {
            abort(405);
        })->name('demo.405');

        Route::get('/406', function () {
            abort(406);
        })->name('demo.406');

        Route::get('/408', function () {
            abort(408);
        })->name('demo.408');

        Route::get('/409', function () {
            abort(409);
        })->name('demo.409');

        Route::get('/410', function () {
            abort(410);
        })->name('demo.410');

        Route::get('/411', function () {
            abort(411);
        })->name('demo.411');

        Route::get('/413', function () {
            abort(413);
        })->name('demo.413');

        Route::get('/414', function () {
            abort(414);
        })->name('demo.414');

        Route::get('/415', function () {
            abort(415);
        })->name('demo.415');

        Route::get('/422', function () {
            abort(422);
        })->name('demo.422');

        Route::get('/429', function () {
            abort(429);
        })->name('demo.429');

        // 5xx Server Error Demo Routes
        Route::get('/500', function () {
            abort(500);
        })->name('demo.500');

        Route::get('/501', function () {
            abort(501);
        })->name('demo.501');

        Route::get('/502', function () {
            abort(502);
        })->name('demo.502');

        Route::get('/503', function () {
            abort(503);
        })->name('demo.503');

        Route::get('/504', function () {
            abort(504);
        })->name('demo.504');

        Route::get('/505', function () {
            abort(505);
        })->name('demo.505');

        Route::get('/507', function () {
            abort(507);
        })->name('demo.507');

        Route::get('/508', function () {
            abort(508);
        })->name('demo.508');

        Route::get('/511', function () {
            abort(511);
        })->name('demo.511');

        // 3xx Redirect Error Demo Routes
        Route::get('/300', function () {
            return response()->view('errors.300', [], 300);
        })->name('demo.300');

        Route::get('/301', function () {
            return response()->view('errors.301', [], 301)->header('Location', url('/dashboard'));
        })->name('demo.301');

        Route::get('/302', function () {
            return response()->view('errors.302', [], 302)->header('Location', url('/dashboard'));
        })->name('demo.302');

        Route::get('/303', function () {
            return response()->view('errors.303', [], 303)->header('Location', url('/dashboard'));
        })->name('demo.303');

        Route::get('/304', function () {
            return response()->view('errors.304', [], 304);
        })->name('demo.304');

        Route::get('/305', function () {
            return response()->view('errors.305', [], 305);
        })->name('demo.305');

        Route::get('/306', function () {
            return response()->view('errors.306', [], 306);
        })->name('demo.306');

        Route::get('/307', function () {
            return response()->view('errors.307', [], 307)->header('Location', url('/dashboard'));
        })->name('demo.307');

        Route::get('/308', function () {
            return response()->view('errors.308', [], 308)->header('Location', url('/dashboard'));
        })->name('demo.308');

        Route::get('/310', function () {
            return response()->view('errors.310', [], 310);
        })->name('demo.310');
    });
}
