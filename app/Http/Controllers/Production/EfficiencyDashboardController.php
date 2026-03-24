<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use App\Models\Production\Timing;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EfficiencyDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display efficiency dashboard overview (all projects)
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Summary cards
        $totalProjects = Project::whereHas('timings', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate])->where('approval_status', 'approved');
        })->count();

        // STANDARDIZED: All calculations use MINUTES as primary unit
        $totalMinutes = Timing::whereBetween('tanggal', [$startDate, $endDate])
            ->where('approval_status', 'approved')
            ->whereNotNull('duration_minutes')
            ->sum('duration_minutes');

        // Convert to hours for display (derived value)
        $totalHours = round($totalMinutes / 60, 2);

        $totalOutput = Timing::whereBetween('tanggal', [$startDate, $endDate])
            ->where('approval_status', 'approved')
            ->whereNotNull('measurement_value')
            ->sum('measurement_value');

        // Total unique employees across all projects
        $totalEmployees = Timing::whereBetween('tanggal', [$startDate, $endDate])
            ->where('approval_status', 'approved')
            ->distinct('employee_id')
            ->count('employee_id');

        // Average efficiency as percentage: (Total Output / Total Minutes) * 60
        // This represents output per hour (normalized to 60 minutes)
        // Cap at 100% maximum for realistic productivity tracking
        $rawEfficiency = $totalMinutes > 0 ? round(($totalOutput / $totalMinutes) * 60, 2) : 0;
        $averageEfficiency = min($rawEfficiency, 100);

        // Projects with metrics - STANDARDIZED: Use minutes as primary unit
        // FILTER: Hanya tampilkan project dengan project_status='Delivered'
        $projects = Project::select('projects.*')
            
            ->where('project_status', 'Delivered')
            ->with(['department', 'projectStatus'])
            ->withCount([
                'timings as sessions_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal', [$startDate, $endDate]);
                },
            ])
            ->addSelect([
                'total_minutes' => Timing::selectRaw('COALESCE(SUM(duration_minutes), 0)')
                    ->whereColumn('project_id', 'projects.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved')
                    ->whereNotNull('duration_minutes'),
                'total_output' => Timing::selectRaw('COALESCE(SUM(measurement_value), 0)')
                    ->whereColumn('project_id', 'projects.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved')
                    ->whereNotNull('measurement_value'),
                'employee_count' => Timing::selectRaw('COUNT(DISTINCT employee_id)')
                    ->whereColumn('project_id', 'projects.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved'),
            ])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('total_minutes')
            ->get();

        // Calculate efficiency and hours (derived) - STANDARDIZED
        $projects->each(function ($project) {
            // Derive hours from minutes for display
            $project->total_hours = round($project->total_minutes / 60, 2);
            // Efficiency: output per hour = (output / minutes) * 60
            $project->efficiency = $project->total_minutes > 0 ? round(($project->total_output / $project->total_minutes) * 60, 2) : 0;
        });

        // Eager load job orders for all projects at once to avoid N+1 queries
        $projectIds = $projects->pluck('id')->toArray();

        $jobOrdersGrouped = JobOrder::whereIn('project_id', $projectIds)
            ->with(['department']) // Eager load department relationship
            ->withCount([
                'timings as sessions_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal', [$startDate, $endDate])->where('approval_status', 'approved');
                },
            ])
            ->addSelect([
                'total_minutes' => Timing::selectRaw('COALESCE(SUM(duration_minutes), 0)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved')
                    ->whereNotNull('duration_minutes'),
                'total_output' => Timing::selectRaw('COALESCE(SUM(measurement_value), 0)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved')
                    ->whereNotNull('measurement_value'),
                'employee_count' => Timing::selectRaw('COUNT(DISTINCT employee_id)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved'),
            ])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('total_minutes')
            ->get()
            ->groupBy('project_id');

        // Attach job orders to their respective projects and calculate efficiency - STANDARDIZED
        $projects->each(function ($project) use ($jobOrdersGrouped) {
            $project->jobOrders = $jobOrdersGrouped->get($project->id, collect());

            // Calculate efficiency and hours for each job order
            $project->jobOrders->each(function ($jobOrder) {
                // Derive hours from minutes
                $jobOrder->total_hours = round($jobOrder->total_minutes / 60, 2);
                // Efficiency: output per hour = (output / minutes) * 60
                $jobOrder->efficiency = $jobOrder->total_minutes > 0 ? round(($jobOrder->total_output / $jobOrder->total_minutes) * 60, 2) : 0;
            });
        });

        return view('efficiency.index', compact('projects', 'totalProjects', 'totalHours', 'totalOutput', 'totalEmployees', 'averageEfficiency', 'startDate', 'endDate'));
    }

    /**
     * Display project detail (job orders breakdown)
     */
    public function projectDetail(Request $request, $projectId)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // ❗ Validasi: Project harus closed DAN delivered
        $project = Project::where('id', $projectId)
            
            ->where('project_status', 'Delivered')
            ->with(['department', 'projectStatus'])
            ->firstOrFail();

        // Project summary - STANDARDIZED: Use minutes
        $projectSummary = Timing::where('project_id', $projectId)
            ->where('approval_status', 'approved')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw(
                '
                COALESCE(SUM(duration_minutes), 0) as total_minutes,
                COALESCE(SUM(measurement_value), 0) as total_output,
                COUNT(*) as total_sessions,
                COUNT(DISTINCT employee_id) as total_employees
            ',
            )
            ->first();

        // Derive hours for display
        $projectSummary->total_hours = round($projectSummary->total_minutes / 60, 2);

        // Job orders in this project - STANDARDIZED: Use minutes
        $jobOrders = JobOrder::where('project_id', $projectId)
            ->with(['department'])
            ->withCount([
                'timings as sessions_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal', [$startDate, $endDate])->where('approval_status', 'approved');
                },
            ])
            ->addSelect([
                'total_minutes' => Timing::selectRaw('COALESCE(SUM(duration_minutes), 0)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved')
                    ->whereNotNull('duration_minutes'),
                'total_output' => Timing::selectRaw('COALESCE(SUM(measurement_value), 0)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved')
                    ->whereNotNull('measurement_value'),
                'employee_count' => Timing::selectRaw('COUNT(DISTINCT employee_id)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->where('approval_status', 'approved'),
            ])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('total_minutes')
            ->get();

        // Calculate efficiency and hours per job order - STANDARDIZED
        $jobOrders->each(function ($jobOrder) {
            $jobOrder->total_hours = round($jobOrder->total_minutes / 60, 2);
            $jobOrder->efficiency = $jobOrder->total_minutes > 0 ? round(($jobOrder->total_output / $jobOrder->total_minutes) * 60, 2) : 0;
        });

        // Timeline data for chart - STANDARDIZED: Use minutes
        $timeline = Timing::where('project_id', $projectId)
            ->where('approval_status', 'approved')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw(
                '
                DATE(tanggal) as date,
                COALESCE(SUM(duration_minutes), 0) as minutes,
                COALESCE(SUM(measurement_value), 0) as output
            ',
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Derive hours for display
        $timeline->each(function ($day) {
            $day->hours = round($day->minutes / 60, 2);
        });

        return view('efficiency.project-detail', compact('project', 'projectSummary', 'jobOrders', 'timeline', 'startDate', 'endDate'));
    }

    /**
     * Display job order detail (employee contributions)
     */
    public function jobOrderDetail(Request $request, $jobOrderId)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // ❗ Load job order dengan validasi project harus closed DAN delivered
        $jobOrder = JobOrder::with([
            'project' => function ($query) {
                $query->where('project_status', 'Delivered');
            },
            'department',
        ])->findOrFail($jobOrderId);

        // Jika project tidak memenuhi criteria, throw 404
        if (!$jobOrder->project || $jobOrder->project->project_status !== 'Delivered') {
            abort(404, 'Job Order not found or project not in delivered status');
        }

        // Job order summary - STANDARDIZED: Use minutes
        $jobOrderSummary = Timing::where('job_order_id', $jobOrderId)
            ->where('approval_status', 'approved')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw(
                '
                COALESCE(SUM(duration_minutes), 0) as total_minutes,
                COALESCE(SUM(measurement_value), 0) as total_output,
                COUNT(*) as total_sessions,
                COUNT(DISTINCT employee_id) as total_employees
            ',
            )
            ->first();

        // Derive hours for display
        $jobOrderSummary->total_hours = round($jobOrderSummary->total_minutes / 60, 2);

        // Employee contributions - STANDARDIZED: Use minutes
        $employeeContributions = Timing::where('job_order_id', $jobOrderId)
            ->where('approval_status', 'approved')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->with(['employee.department'])
            ->selectRaw(
                '
                employee_id,
                COALESCE(SUM(duration_minutes), 0) as total_minutes,
                COALESCE(SUM(measurement_value), 0) as total_output,
                COUNT(*) as sessions_count,
                MIN(tanggal) as first_work_date,
                MAX(tanggal) as last_work_date
            ',
            )
            ->groupBy('employee_id')
            ->orderByDesc('total_minutes')
            ->get();

        // Calculate efficiency and hours per employee - STANDARDIZED
        $employeeContributions->each(function ($contribution) {
            $contribution->total_hours = round($contribution->total_minutes / 60, 2);
            $contribution->efficiency = $contribution->total_minutes > 0 ? round(($contribution->total_output / $contribution->total_minutes) * 60, 2) : 0;
            $contribution->hours_percentage = 0;
        });

        // Calculate percentage contribution based on minutes
        $totalMinutes = $employeeContributions->sum('total_minutes');
        if ($totalMinutes > 0) {
            $employeeContributions->each(function ($contribution) use ($totalMinutes) {
                $contribution->hours_percentage = round(($contribution->total_minutes / $totalMinutes) * 100, 1);
            });
        }

        // Timeline by employee (for stacked chart) - STANDARDIZED: Use minutes
        $employeeTimeline = Timing::where('job_order_id', $jobOrderId)
            ->where('approval_status', 'approved')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->with('employee:id,name')
            ->selectRaw(
                '
                DATE(tanggal) as date,
                employee_id,
                COALESCE(SUM(duration_minutes), 0) as minutes,
                COALESCE(SUM(measurement_value), 0) as output
            ',
            )
            ->groupBy('date', 'employee_id')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->hours = round($item->minutes / 60, 2);
                return $item;
            })
            ->groupBy('date');

        // Progress trend (for progress mode)
        $progressTrend = Timing::where('job_order_id', $jobOrderId)
            ->where('approval_status', 'approved')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereNotNull('department_specific_data')
            ->get()
            ->map(function ($timing) {
                $deptData = $timing->department_specific_data ?? [];
                return [
                    'date' => $timing->tanggal->format('Y-m-d'),
                    'time' => $timing->end_time ?? $timing->start_time,
                    'employee' => $timing->employee->name ?? 'Unknown',
                    'current_progress' => $deptData['current_progress'] ?? 0,
                ];
            })
            ->sortBy('date');

        return view('efficiency.job-order-detail', compact('jobOrder', 'jobOrderSummary', 'employeeContributions', 'employeeTimeline', 'progressTrend', 'startDate', 'endDate'));
    }

    /**
     * Export project efficiency to Excel
     */
    public function exportProject($projectId, Request $request)
    {
        // TODO: Implement Excel export using Maatwebsite\Excel
        return back()->with('info', 'Export feature coming soon');
    }

    /**
     * Export job order detail to Excel
     */
    public function exportJobOrder($jobOrderId, Request $request)
    {
        // TODO: Implement Excel export
        return back()->with('info', 'Export feature coming soon');
    }
}
