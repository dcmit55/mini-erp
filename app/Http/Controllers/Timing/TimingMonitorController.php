<?php

namespace App\Http\Controllers\Timing;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use Illuminate\Http\Request;

class TimingMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display running timing sessions monitor (real-time dashboard)
     */
    public function index()
    {
        // Get all running sessions grouped by department
        $runningSessions = Timing::running()
            ->today()
            ->with(['employee.department', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get()
            ->groupBy(function ($timing) {
                return $timing->employee->department->name ?? 'Unknown';
            });

        // Calculate statistics
        $totalRunning = Timing::running()->today()->count();
        $totalEmployees = Timing::running()->today()->distinct('employee_id')->count();

        // Get costume timing running count
        $costumeRunning = Timing::running()
            ->today()
            ->whereHas('employee.department', function ($query) {
                $query->where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%');
            })
            ->count();

        // Get animatronics timing running count
        $animatronicsRunning = Timing::running()
            ->today()
            ->whereHas('employee.department', function ($query) {
                $query->where('name', 'LIKE', '%animatronic%');
            })
            ->count();

        return view('timing.monitor.index', compact('runningSessions', 'totalRunning', 'totalEmployees', 'costumeRunning', 'animatronicsRunning'));
    }

    /**
     * Get running sessions via AJAX for auto-refresh
     */
    public function getRunning()
    {
        $runningSessions = Timing::running()
            ->today()
            ->with(['employee.department', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'sessions' => $runningSessions->map(function ($timing) {
                return [
                    'id' => $timing->id,
                    'employee_name' => $timing->employee->name ?? 'Unknown',
                    'employee_photo' => $timing->employee->photo ?? null,
                    'employee_position' => $timing->employee->position ?? 'N/A',
                    'department' => $timing->employee->department->name ?? 'Unknown',
                    'job_order_name' => $timing->jobOrder->name ?? 'N/A',
                    'project_name' => $timing->jobOrder->project->name ?? 'N/A',
                    'step' => $timing->step,
                    'parts' => $timing->parts,
                    'start_time' => $timing->start_time,
                    'duration' => $timing->getDurationAttribute(),
                    'department_data' => $timing->department_data,
                ];
            }),
            'statistics' => [
                'total_running' => Timing::running()->today()->count(),
                'total_employees' => Timing::running()->today()->distinct('employee_id')->count(),
                'costume_running' => Timing::running()
                    ->today()
                    ->whereHas('employee.department', function ($query) {
                        $query->where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%');
                    })
                    ->count(),
                'animatronics_running' => Timing::running()
                    ->today()
                    ->whereHas('employee.department', function ($query) {
                        $query->where('name', 'LIKE', '%animatronic%');
                    })
                    ->count(),
            ],
        ]);
    }
}
