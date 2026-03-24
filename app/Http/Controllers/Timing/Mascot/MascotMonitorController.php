<?php

namespace App\Http\Controllers\Timing\Mascot;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use Illuminate\Http\Request;

class MascotMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display running mascot timing sessions (real-time monitor)
     */
    public function index()
    {
        // Get mascot department
        $mascotDept = Department::where('name', 'LIKE', '%mascot%')->first();

        if (!$mascotDept) {
            return redirect()->route('mascot-timing.index')->with('error', 'Mascot department not found.');
        }

        // Get all running sessions for mascot department
        $runningSessions = Timing::running()
            ->today()
            ->whereHas('employee', function ($query) use ($mascotDept) {
                $query->where('department_id', $mascotDept->id);
            })
            ->with(['employee.department', 'project', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        // Calculate statistics
        $totalRunning = $runningSessions->count();
        $totalEmployees = $runningSessions->unique('employee_id')->count();

        // Group by project for better organization
        $groupedSessions = $runningSessions->groupBy(function ($timing) {
            return $timing->project->name ?? 'Unknown Project';
        });

        $units = Unit::orderBy('name')->get();

        return view('timing.mascot.monitor', compact('runningSessions', 'groupedSessions', 'totalRunning', 'totalEmployees', 'mascotDept', 'units'));
    }

    /**
     * Get running sessions via AJAX for auto-refresh
     */
    public function getRunning()
    {
        // Get mascot department
        $mascotDept = Department::where('name', 'LIKE', '%mascot%')->first();

        if (!$mascotDept) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Mascot department not found',
                ],
                404,
            );
        }

        $runningSessions = Timing::running()
            ->today()
            ->whereHas('employee', function ($query) use ($mascotDept) {
                $query->where('department_id', $mascotDept->id);
            })
            ->with(['employee.department', 'project', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'sessions' => $runningSessions->map(function ($timing) {
                // Get department specific data
                $deptData = $timing->department_specific_data ?? [];
                $trackingMode = $deptData['tracking_mode'] ?? 'progress';

                return [
                    'id' => $timing->id,
                    'employee_name' => $timing->employee->name ?? 'Unknown',
                    'employee_photo' => $timing->employee->photo ?? null,
                    'employee_position' => $timing->employee->position ?? 'N/A',
                    'department' => $timing->employee->department->name ?? 'Unknown',
                    'job_order_name' => $timing->jobOrder->name ?? 'N/A',
                    'project_name' => $timing->project->name ?? 'N/A',
                    'step' => $timing->step,
                    'parts' => $timing->parts,
                    'start_time' => $timing->start_time,
                    'duration' => $timing->getDurationAttribute(),
                    'tracking_mode' => $trackingMode,
                    'current_progress' => $deptData['current_progress'] ?? 0,
                    'stage' => $deptData['stage'] ?? 0,
                ];
            }),
            'statistics' => [
                'total_running' => $runningSessions->count(),
                'total_employees' => $runningSessions->unique('employee_id')->count(),
            ],
        ]);
    }
}
