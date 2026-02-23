<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Services\EmployeePerformanceService;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Employee Performance Controller
 *
 * Manages employee performance ranking and analytics
 * Based on duration_minutes and measurement_value data
 */
class EmployeePerformanceController extends Controller
{
    protected EmployeePerformanceService $performanceService;

    public function __construct(EmployeePerformanceService $performanceService)
    {
        $this->middleware('auth');
        $this->performanceService = $performanceService;
    }

    /**
     * Display employee productivity ranking dashboard
     * PART 1: Support filters - date range, department, job_order, employee
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Parse date filters
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $departmentId = $request->input('department_id');
        $jobOrderId = $request->input('job_order_id'); // PART 1: Job Order filter
        $employeeId = $request->input('employee_id'); // PART 1: Employee filter

        // Get employee rankings with all filters
        $rankings = $this->performanceService->getEmployeeRanking($startDate, $endDate, $departmentId, $jobOrderId, $employeeId);

        // Get departments for filter
        $departments = Department::orderBy('name')->get();

        // Get job orders for filter
        $jobOrders = \App\Models\Production\JobOrder::with('project')->orderBy('id', 'desc')->limit(100)->get();

        // Get employees for filter
        $employees = Employee::orderBy('name')->get();

        // PART 5: Return clean JSON for API requests
        if ($request->wantsJson() || ($request->has('format') && $request->format === 'json')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'rankings' => $rankings->values(),
                    'filters' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'department_id' => $departmentId,
                        'job_order_id' => $jobOrderId,
                        'employee_id' => $employeeId,
                    ],
                    'summary' => [
                        'total_employees' => $rankings->count(),
                        'average_productivity' => $rankings->avg('productivity_score'),
                        'highest_productivity' => $rankings->max('productivity_score'),
                        'lowest_productivity' => $rankings->min('productivity_score'),
                    ],
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'version' => '1.0',
                ],
            ]);
        }

        return view('production.performance.index', compact('rankings', 'departments', 'jobOrders', 'employees', 'startDate', 'endDate', 'departmentId', 'jobOrderId', 'employeeId'));
    }

    /**
     * Show detailed performance report for specific employee
     *
     * @param Request $request
     * @param int $employeeId
     * @return \Illuminate\View\View
     */
    /**
     * Export performance ranking to Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $departmentId = $request->input('department_id');
        $jobOrderId = $request->input('job_order_id');
        $employeeId = $request->input('employee_id');

        $rankings = $this->performanceService->getEmployeeRanking($startDate, $endDate, $departmentId, $jobOrderId, $employeeId);

        // Generate filename with date range and filters
        $filename = 'Employee_Performance_Ranking_' . $startDate->format('Ymd') . '_to_' . $endDate->format('Ymd');

        if ($jobOrderId) {
            $filename .= '_JO' . $jobOrderId;
        }
        if ($employeeId) {
            $filename .= '_Emp' . $employeeId;
        }

        $filename .= '.xlsx';

        // Export using Maatwebsite Excel
        return \Excel::download(new \App\Exports\EmployeePerformanceExport($rankings, $startDate, $endDate), $filename);
    }

    /**
     * API endpoint: Get performance score for employee
     *
     * @param Request $request
     * @param int $employeeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerformanceScore(Request $request, int $employeeId)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : null;

        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : null;

        $score = $this->performanceService->calculateEmployeeProductivityScore($employeeId, $startDate, $endDate);

        return response()->json([
            'employee_id' => $employeeId,
            'performance_score' => $score,
            'filters' => [
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
            ],
        ]);
    }
}
