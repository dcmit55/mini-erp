<?php

namespace App\Http\Controllers\Timing\Animatronics;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use App\Models\Admin\Department;
use App\Models\Hr\AttendanceLog;
use App\Services\Timing\TimingBreakService;
use Illuminate\Http\Request;

class AnimatronicsMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display running & frozen animatronics timing sessions (real-time monitor)
     */
    public function index()
    {
        $animatronicsDept = Department::where('name', 'LIKE', '%animatronic%')
            ->orWhere('name', 'LIKE', '%animation%')->first();

        if (!$animatronicsDept) {
            return redirect()->route('animatronics-timing.index')
                ->with('error', 'Animatronics department not found.');
        }

        $runningSessions = Timing::whereIn('status', ['on progress', 'frozen'])
            ->today()
            ->whereHas('employee', function ($query) use ($animatronicsDept) {
                $query->where('department_id', $animatronicsDept->id);
            })
            ->with(['employee.department', 'project', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        $totalRunning   = $runningSessions->where('status', 'on progress')->count();
        $totalFrozen    = $runningSessions->where('status', 'frozen')->count();
        $totalEmployees = $runningSessions->unique('employee_id')->count();

        $timerModeSessions = $runningSessions->filter(function ($timing) {
            $data = $timing->department_specific_data ?? [];
            return ($data['tracking_mode'] ?? 'timer') === 'timer';
        });

        $progressModeSessions = $runningSessions->filter(function ($timing) {
            $data = $timing->department_specific_data ?? [];
            return ($data['tracking_mode'] ?? 'timer') === 'progress';
        });

        $groupedSessions = $runningSessions->groupBy(function ($timing) {
            return $timing->project->name ?? 'Unknown Project';
        });

        return view('timing.animatronics.monitor', compact(
            'runningSessions', 'groupedSessions', 'totalRunning', 'totalFrozen',
            'totalEmployees', 'timerModeSessions', 'progressModeSessions', 'animatronicsDept'
        ));
    }

    /**
     * Get employees who clocked in today but have no active timing session (for monitor feed)
     */
    public function getClockedIn()
    {
        $dept = Department::where('name', 'LIKE', '%animatronic%')
            ->orWhere('name', 'LIKE', '%animation%')->first();

        if (!$dept) {
            return response()->json(['success' => true, 'employees' => [], 'count' => 0]);
        }

        $activeEmployeeIds = Timing::whereIn('status', ['on progress', 'frozen', 'paused'])
            ->today()
            ->pluck('employee_id')
            ->toArray();

        $employees = AttendanceLog::whereDate('date', today())
            ->whereNotNull('clock_in')
            ->whereHas('employee', fn($q) => $q
                ->where('department_id', $dept->id)
                ->whereNotIn('id', $activeEmployeeIds))
            ->with('employee')
            ->orderBy('clock_in')
            ->get()
            ->map(fn($log) => [
                'id'       => $log->employee->id,
                'name'     => $log->employee->name,
                'position' => $log->employee->position ?? '—',
                'photo'    => $log->employee->photo,
                'clock_in' => optional($log->clock_in)->format('H:i'),
                'initials' => strtoupper(substr($log->employee->name, 0, 1)),
            ]);

        return response()->json([
            'success'   => true,
            'employees' => $employees,
            'count'     => $employees->count(),
        ]);
    }

    /**
     * Get running & frozen sessions via AJAX for auto-refresh
     */
    public function getRunning(TimingBreakService $breakService)
    {
        $breakService->run();

        $animatronicsDept = Department::where('name', 'LIKE', '%animatronic%')
            ->orWhere('name', 'LIKE', '%animation%')->first();

        if (!$animatronicsDept) {
            return response()->json(['success' => false, 'message' => 'Animatronics department not found'], 404);
        }

        $runningSessions = Timing::whereIn('status', ['on progress', 'frozen'])
            ->today()
            ->whereHas('employee', function ($query) use ($animatronicsDept) {
                $query->where('department_id', $animatronicsDept->id);
            })
            ->with(['employee.department', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'success'  => true,
            'sessions' => $runningSessions->map(function ($timing) {
                $departmentData = $timing->department_specific_data ?? [];
                $isFrozen       = $timing->isFrozen();

                return [
                    'id'              => $timing->id,
                    'employee_name'   => $timing->employee->name ?? 'Unknown',
                    'employee_photo'  => $timing->employee->photo ?? null,
                    'employee_position' => $timing->employee->position ?? 'N/A',
                    'department'      => $timing->employee->department->name ?? 'Unknown',
                    'job_order_name'  => $timing->jobOrder->name ?? 'N/A',
                    'project_name'    => $timing->jobOrder->project->name ?? 'N/A',
                    'step'            => $timing->step,
                    'parts'           => $timing->parts,
                    'start_time'      => $timing->start_time,
                    'is_frozen'       => $isFrozen,
                    'frozen_duration' => $isFrozen ? ($departmentData['frozen_duration'] ?? '00:00:00') : null,
                    'duration'        => $isFrozen
                        ? ($departmentData['frozen_duration'] ?? '00:00:00')
                        : $timing->getDurationAttribute(),
                    'tracking_mode'   => $departmentData['tracking_mode'] ?? 'timer',
                    'previous_progress' => $timing->previous_progress ?? 0,
                ];
            }),
            'statistics' => [
                'total_running'  => $runningSessions->where('status', 'on progress')->count(),
                'total_frozen'   => $runningSessions->where('status', 'frozen')->count(),
                'total_employees' => $runningSessions->unique('employee_id')->count(),
                'timer_mode'     => $runningSessions->filter(fn($t) => ($t->department_specific_data['tracking_mode'] ?? 'timer') === 'timer')->count(),
                'progress_mode'  => $runningSessions->filter(fn($t) => ($t->department_specific_data['tracking_mode'] ?? 'timer') === 'progress')->count(),
            ],
        ]);
    }
}
