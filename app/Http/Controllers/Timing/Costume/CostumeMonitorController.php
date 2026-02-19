<?php

namespace App\Http\Controllers\Timing\Costume;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use App\Models\Admin\Department;
use Illuminate\Http\Request;

class CostumeMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display running costume timing sessions (real-time monitor)
     */
    public function index()
    {
        // Get costume department
        $costumeDept = Department::where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%')->first();

        if (!$costumeDept) {
            return redirect()->route('costume-timing.index')->with('error', 'Costume department not found.');
        }

        // Get all running sessions for costume department
        $runningSessions = Timing::running()
            ->today()
            ->whereHas('employee', function ($query) use ($costumeDept) {
                $query->where('department_id', $costumeDept->id);
            })
            ->with(['employee.department', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        // Calculate statistics
        $totalRunning = $runningSessions->count();
        $totalEmployees = $runningSessions->unique('employee_id')->count();

        // Group by job order for better organization
        $groupedSessions = $runningSessions->groupBy(function ($timing) {
            return $timing->jobOrder->name ?? 'Unknown Job Order';
        });

        return view('timing.costume.monitor', compact('runningSessions', 'groupedSessions', 'totalRunning', 'totalEmployees', 'costumeDept'));
    }

    /**
     * Get running sessions via AJAX for auto-refresh
     */
    public function getRunning()
    {
        // Get costume department
        $costumeDept = Department::where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%')->first();

        if (!$costumeDept) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Costume department not found',
                ],
                404,
            );
        }

        $runningSessions = Timing::running()
            ->today()
            ->whereHas('employee', function ($query) use ($costumeDept) {
                $query->where('department_id', $costumeDept->id);
            })
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
                ];
            }),
            'statistics' => [
                'total_running' => $runningSessions->count(),
                'total_employees' => $runningSessions->unique('employee_id')->count(),
            ],
        ]);
    }
}
