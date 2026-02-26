<?php

namespace App\Http\Controllers\Timing\Animatronics;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use App\Models\Admin\Department;
use Illuminate\Http\Request;

class AnimatronicsMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display running animatronics timing sessions (real-time monitor)
     */
    public function index()
    {
        // Get animatronics department
        $animatronicsDept = Department::where('name', 'LIKE', '%animatronic%')->orWhere('name', 'LIKE', '%animation%')->first();

        if (!$animatronicsDept) {
            return redirect()->route('animatronics-timing.index')->with('error', 'Animatronics department not found.');
        }

        // Get all running sessions for animatronics department
        $runningSessions = Timing::running()
            ->today()
            ->whereHas('employee', function ($query) use ($animatronicsDept) {
                $query->where('department_id', $animatronicsDept->id);
            })
            ->with(['employee.department', 'project', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        // Calculate statistics
        $totalRunning = $runningSessions->count();
        $totalEmployees = $runningSessions->unique('employee_id')->count();

        // Group by tracking mode
        $timerModeSessions = $runningSessions->filter(function ($timing) {
            $data = $timing->department_data;
            return is_array($data) && isset($data['tracking_mode']) && $data['tracking_mode'] === 'timer';
        });

        $progressModeSessions = $runningSessions->filter(function ($timing) {
            $data = $timing->department_data;
            return is_array($data) && isset($data['tracking_mode']) && $data['tracking_mode'] === 'progress';
        });

        // Group by project
        $groupedSessions = $runningSessions->groupBy(function ($timing) {
            return $timing->project->name ?? 'Unknown Project';
        });

        return view('timing.animatronics.monitor', compact('runningSessions', 'groupedSessions', 'totalRunning', 'totalEmployees', 'timerModeSessions', 'progressModeSessions', 'animatronicsDept'));
    }

    /**
     * Get running sessions via AJAX for auto-refresh
     */
    public function getRunning()
    {
        // Get animatronics department
        $animatronicsDept = Department::where('name', 'LIKE', '%animatronic%')->orWhere('name', 'LIKE', '%animation%')->first();

        if (!$animatronicsDept) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Animatronics department not found',
                ],
                404,
            );
        }

        $runningSessions = Timing::running()
            ->today()
            ->whereHas('employee', function ($query) use ($animatronicsDept) {
                $query->where('department_id', $animatronicsDept->id);
            })
            ->with(['employee.department', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'sessions' => $runningSessions->map(function ($timing) {
                $departmentData = $timing->department_data ?? [];
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
                    'tracking_mode' => $departmentData['tracking_mode'] ?? 'timer',
                    'previous_progress' => $timing->previous_progress ?? 0,
                ];
            }),
            'statistics' => [
                'total_running' => $runningSessions->count(),
                'total_employees' => $runningSessions->unique('employee_id')->count(),
                'timer_mode' => $runningSessions
                    ->filter(function ($t) {
                        $data = $t->department_data;
                        return is_array($data) && isset($data['tracking_mode']) && $data['tracking_mode'] === 'timer';
                    })
                    ->count(),
                'progress_mode' => $runningSessions
                    ->filter(function ($t) {
                        $data = $t->department_data;
                        return is_array($data) && isset($data['tracking_mode']) && $data['tracking_mode'] === 'progress';
                    })
                    ->count(),
            ],
        ]);
    }
}
