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
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        })->count();

        $totalHours = Timing::whereBetween('tanggal', [$startDate, $endDate])
            ->whereNotNull('duration_hours')
            ->sum('duration_hours');

        $totalOutput = Timing::whereBetween('tanggal', [$startDate, $endDate])
            ->whereNotNull('measurement_value')
            ->sum('measurement_value');

        // Projects with metrics
        $projects = Project::select('projects.*')
            ->with(['department', 'projectStatus'])
            ->withCount([
                'timings as sessions_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal', [$startDate, $endDate]);
                },
            ])
            ->addSelect([
                'total_hours' => Timing::selectRaw('COALESCE(SUM(duration_hours), 0)')
                    ->whereColumn('project_id', 'projects.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->whereNotNull('duration_hours'),
                'total_output' => Timing::selectRaw('COALESCE(SUM(measurement_value), 0)')
                    ->whereColumn('project_id', 'projects.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->whereNotNull('measurement_value'),
                'employee_count' => Timing::selectRaw('COUNT(DISTINCT employee_id)')
                    ->whereColumn('project_id', 'projects.id')
                    ->whereBetween('tanggal', [$startDate, $endDate]),
            ])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('total_hours')
            ->get();

        // Calculate efficiency (total_output / total_hours)
        $projects->each(function ($project) {
            $project->efficiency = $project->total_hours > 0 ? round($project->total_output / $project->total_hours, 2) : 0;
        });

        return view('efficiency.index', compact('projects', 'totalProjects', 'totalHours', 'totalOutput', 'startDate', 'endDate'));
    }

    /**
     * Display project detail (job orders breakdown)
     */
    public function projectDetail(Request $request, $projectId)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $project = Project::with(['department', 'projectStatus'])->findOrFail($projectId);

        // Project summary
        $projectSummary = Timing::where('project_id', $projectId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw(
                '
                COALESCE(SUM(duration_hours), 0) as total_hours,
                COALESCE(SUM(measurement_value), 0) as total_output,
                COUNT(*) as total_sessions,
                COUNT(DISTINCT employee_id) as total_employees
            ',
            )
            ->first();

        // Job orders in this project
        $jobOrders = JobOrder::where('project_id', $projectId)
            ->with(['department'])
            ->withCount([
                'timings as sessions_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal', [$startDate, $endDate]);
                },
            ])
            ->addSelect([
                'total_hours' => Timing::selectRaw('COALESCE(SUM(duration_hours), 0)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->whereNotNull('duration_hours'),
                'total_output' => Timing::selectRaw('COALESCE(SUM(measurement_value), 0)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->whereNotNull('measurement_value'),
                'employee_count' => Timing::selectRaw('COUNT(DISTINCT employee_id)')
                    ->whereColumn('job_order_id', 'job_orders.id')
                    ->whereBetween('tanggal', [$startDate, $endDate]),
            ])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('total_hours')
            ->get();

        // Calculate efficiency per job order
        $jobOrders->each(function ($jobOrder) {
            $jobOrder->efficiency = $jobOrder->total_hours > 0 ? round($jobOrder->total_output / $jobOrder->total_hours, 2) : 0;
        });

        // Timeline data for chart (hours vs output per day)
        $timeline = Timing::where('project_id', $projectId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw(
                '
                DATE(tanggal) as date,
                COALESCE(SUM(duration_hours), 0) as hours,
                COALESCE(SUM(measurement_value), 0) as output
            ',
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('efficiency.project-detail', compact('project', 'projectSummary', 'jobOrders', 'timeline', 'startDate', 'endDate'));
    }

    /**
     * Display job order detail (employee contributions)
     */
    public function jobOrderDetail(Request $request, $jobOrderId)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $jobOrder = JobOrder::with(['project', 'department'])->findOrFail($jobOrderId);

        // Job order summary
        $jobOrderSummary = Timing::where('job_order_id', $jobOrderId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw(
                '
                COALESCE(SUM(duration_hours), 0) as total_hours,
                COALESCE(SUM(measurement_value), 0) as total_output,
                COUNT(*) as total_sessions,
                COUNT(DISTINCT employee_id) as total_employees
            ',
            )
            ->first();

        // Employee contributions
        $employeeContributions = Timing::where('job_order_id', $jobOrderId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->with(['employee.department'])
            ->selectRaw(
                '
                employee_id,
                COALESCE(SUM(duration_hours), 0) as total_hours,
                COALESCE(SUM(measurement_value), 0) as total_output,
                COUNT(*) as sessions_count,
                MIN(tanggal) as first_work_date,
                MAX(tanggal) as last_work_date
            ',
            )
            ->groupBy('employee_id')
            ->orderByDesc('total_hours')
            ->get();

        // Calculate efficiency per employee
        $employeeContributions->each(function ($contribution) {
            $contribution->efficiency = $contribution->total_hours > 0 ? round($contribution->total_output / $contribution->total_hours, 2) : 0;
            $contribution->hours_percentage = 0;
        });

        // Calculate percentage contribution
        $totalHours = $employeeContributions->sum('total_hours');
        if ($totalHours > 0) {
            $employeeContributions->each(function ($contribution) use ($totalHours) {
                $contribution->hours_percentage = round(($contribution->total_hours / $totalHours) * 100, 1);
            });
        }

        // Timeline by employee (for stacked chart)
        $employeeTimeline = Timing::where('job_order_id', $jobOrderId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->with('employee:id,name')
            ->selectRaw(
                '
                DATE(tanggal) as date,
                employee_id,
                COALESCE(SUM(duration_hours), 0) as hours,
                COALESCE(SUM(measurement_value), 0) as output
            ',
            )
            ->groupBy('date', 'employee_id')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        // Progress trend (for progress mode)
        $progressTrend = Timing::where('job_order_id', $jobOrderId)
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
