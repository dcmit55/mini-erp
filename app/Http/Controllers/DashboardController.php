<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use App\Models\Logistic\MaterialRequest;
use App\Models\Logistic\GoodsIn;
use App\Models\Logistic\GoodsOut;
use App\Models\Logistic\MaterialUsage;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use App\Models\Logistic\Category;
use App\Models\Production\Timing;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_logistic', 'admin_mascot', 'admin_costume', 'admin_animatronic', 'admin_finance', 'admin_procurement', 'admin_hr', 'admin', 'timing', 'general'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $user = Auth::user();

        // Basic Metrics
        $inventoryCount = Inventory::count();
        $projectCount = Project::count();
        $employeeCount = Employee::count();
        $departmentCount = Department::count();
        $totalCategories = Inventory::select('category')->distinct()->count();

        // Request Statistics
        $pendingRequests = MaterialRequest::where('status', 'pending')->count();
        $approvedRequests = MaterialRequest::where('status', 'approved')->count();
        $deliveredRequests = MaterialRequest::where('status', 'delivered')->count();
        $totalRequests = MaterialRequest::count();

        // Project Statistics
        $activeProjects = Project::whereNull('finish_date')->count();
        $completedProjects = Project::whereNotNull('finish_date')->count();
        $projectsThisMonth = Project::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Inventory Statistics
        $lowStockItems = Inventory::whereHas('batches', function ($q) {
            $q->whereNull('deleted_at')->where('qty_remaining', '>', 0);
        })
            ->withSum(
                [
                    'batches as total_qty' => function ($q) {
                        $q->whereNull('deleted_at');
                    },
                ],
                'qty_remaining',
            )
            ->get()
            ->filter(fn($i) => ($i->total_qty ?? 0) <= 10)
            ->count();
        // Data untuk low stock items di dashboard dengan relasi category dan supplier
        $veryLowStockItems = Inventory::with(['category', 'supplier'])
            ->withComputedStock()
            ->get()
            ->filter(fn($i) => $i->quantity < 3 && $i->quantity >= 0)
            ->sortBy('quantity')
            ->values();
        $outOfStockItems = Inventory::withSum(
            [
                'batches as total_qty' => function ($q) {
                    $q->whereNull('deleted_at');
                },
            ],
            'qty_remaining',
        )
            ->get()
            ->filter(fn($i) => ($i->total_qty ?? 0) <= 0)
            ->count();
        $totalInventoryValue = DB::table('inventory_batches as ib')->join('currencies', 'ib.currency_id', '=', 'currencies.id')->whereNull('ib.deleted_at')->where('ib.qty_remaining', '>', 0)->selectRaw('SUM(ib.qty_remaining * ib.unit_price * currencies.exchange_rate) as total_value')->value('total_value') ?? 0;

        // Recent Activities
        $recentGoodsIn = GoodsIn::with(['inventory', 'project'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentGoodsOut = GoodsOut::with(['inventory', 'project'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentRequests = MaterialRequest::with(['inventory', 'project', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top Categories by Inventory Count
        $topCategories = Category::withCount('inventories')->orderBy('inventories_count', 'desc')->limit(5)->get();

        // Department Statistics
        $departmentStats = Department::withCount(['projects', 'users'])->get();

        // Monthly Trends (last 6 months)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'projects' => Project::whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
                'goods_in' => GoodsIn::whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
                'goods_out' => GoodsOut::whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
                'requests' => MaterialRequest::whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
            ];
        }

        // Upcoming Project Deadlines
        $upcomingDeadlines = Project::whereNotNull('deadline')
            ->whereNull('finish_date')
            ->where('deadline', '>=', Carbon::now())
            ->where('deadline', '<=', Carbon::now()->addDays(30))
            ->with('departments')
            ->orderBy('deadline')
            ->limit(10)
            ->get();

        // Material Usage This Month
        $materialUsageThisMonth = MaterialUsage::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('used_quantity');

        // Production Efficiency Metrics (This Month)
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $totalProductionMinutes = Timing::whereBetween('tanggal', [$startDate, $endDate])
            ->where('approval_status', 'approved')
            ->whereNotNull('duration_minutes')
            ->sum('duration_minutes');

        $totalProductionHours = round($totalProductionMinutes / 60, 1);

        $totalProductionOutput = Timing::whereBetween('tanggal', [$startDate, $endDate])
            ->where('approval_status', 'approved')
            ->whereNotNull('measurement_value')
            ->sum('measurement_value');

        $activeProductionProjects = Project::whereHas('timings', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate])->where('approval_status', 'approved');
        })->count();

        // Pass veryLowStockItems ke view untuk ditampilkan di dashboard
        return view('dashboard', compact('user', 'inventoryCount', 'projectCount', 'employeeCount', 'departmentCount', 'pendingRequests', 'approvedRequests', 'deliveredRequests', 'totalRequests', 'activeProjects', 'completedProjects', 'projectsThisMonth', 'lowStockItems', 'veryLowStockItems', 'outOfStockItems', 'totalInventoryValue', 'recentGoodsIn', 'recentGoodsOut', 'recentRequests', 'topCategories', 'departmentStats', 'monthlyData', 'upcomingDeadlines', 'materialUsageThisMonth', 'totalCategories', 'totalProductionHours', 'totalProductionOutput', 'activeProductionProjects'));
    }
}
