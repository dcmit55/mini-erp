<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\JobOrderTypeGrading;
use App\Services\Lark\LarkJobOrderTypeGradingSyncService;
use App\Models\Admin\Department;
use App\Models\Logistic\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JobOrderTypeGradingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $search     = $request->input('search', '');
        $categoryId = $request->input('category_id', '');
        $deptId     = $request->input('department_id', '');

        $query = JobOrderTypeGrading::with(['category', 'departments'])
            ->whereNull('deleted_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('job_type_grade', 'LIKE', "%{$search}%")
                  ->orWhere('grading', 'LIKE', "%{$search}%")
                  ->orWhere('job_type', 'LIKE', "%{$search}%")
                  ->orWhere('product_sub_category', 'LIKE', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($deptId) {
            $query->whereHas('departments', fn($q) => $q->where('departments.id', $deptId));
        }

        $gradings = $query->orderBy('job_type_grade')->paginate(50)->withQueryString();

        $categories  = Category::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        $lastSync = JobOrderTypeGrading::whereNotNull('last_sync_at')
            ->latest('last_sync_at')
            ->value('last_sync_at');

        return view('production.job-order-type-gradings.index', compact(
            'gradings', 'categories', 'departments', 'lastSync', 'search'
        ));
    }

    /**
     * Sync langsung via AJAX — return JSON hasil sync
     */
    public function syncFromLark(Request $request, LarkJobOrderTypeGradingSyncService $service)
    {
        set_time_limit(120);

        try {
            $stats = $service->sync();

            return response()->json([
                'status'  => 'done',
                'message' => "Sync selesai. Created: {$stats['created']}, Updated: {$stats['updated']}, Deactivated: {$stats['deactivated']}",
                'stats'   => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('JobOrderTypeGrading syncFromLark failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'failed',
                'message' => 'Sync gagal: ' . $e->getMessage(),
            ], 500);
        }
    }
}
