<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory;
use App\Models\Project;
use App\Models\MaterialRequest;
use App\Models\GoodsIn;
use App\Models\GoodsOut;
use App\Models\MaterialUsage;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Category;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_logistic', 'admin_mascot', 'admin_costume', 'admin_animatronic', 'admin_finance', 'admin_procurement', 'admin', 'general'];
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
        $lowStockItems = Inventory::where('quantity', '<=', 10)->count();
        $outOfStockItems = Inventory::where('quantity', '<=', 0)->count();
        $totalInventoryValue = Inventory::join('currencies', 'inventories.currency_id', '=', 'currencies.id')->selectRaw('SUM(inventories.quantity * inventories.price * currencies.exchange_rate) as total_value')->value('total_value') ?? 0;

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
            ->limit(5)
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
            ->with('department')
            ->orderBy('deadline')
            ->limit(5)
            ->get();

        // Material Usage This Month
        $materialUsageThisMonth = MaterialUsage::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('used_quantity');

        return view('dashboard', compact('user', 'inventoryCount', 'projectCount', 'employeeCount', 'departmentCount', 'pendingRequests', 'approvedRequests', 'deliveredRequests', 'totalRequests', 'activeProjects', 'completedProjects', 'projectsThisMonth', 'lowStockItems', 'outOfStockItems', 'totalInventoryValue', 'recentGoodsIn', 'recentGoodsOut', 'recentRequests', 'topCategories', 'departmentStats', 'monthlyData', 'upcomingDeadlines', 'materialUsageThisMonth', 'totalCategories'));
    }
}
