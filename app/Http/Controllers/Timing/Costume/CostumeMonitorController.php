<?php

namespace App\Http\Controllers\Timing\Costume;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use App\Models\Admin\Department;
use App\Models\Hr\AttendanceLog;
use App\Models\Logistic\Unit;
use App\Services\Timing\TimingBreakService;
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

        // Get all running + frozen sessions for costume department
        $runningSessions = Timing::whereIn('status', ['on progress', 'frozen'])
            ->today()
            ->whereHas('employee', function ($query) use ($costumeDept) {
                $query->where('department_id', $costumeDept->id);
            })
            ->with(['employee.department', 'project', 'jobOrder.project'])
            ->orderBy('start_time', 'desc')
            ->get();

        // Calculate statistics
        $totalRunning = $runningSessions->where('status', 'on progress')->count();
        $totalFrozen = $runningSessions->where('status', 'frozen')->count();
        $totalEmployees = $runningSessions->unique('employee_id')->count();

        // Group by project for better organization
        $groupedSessions = $runningSessions->groupBy(function ($timing) {
            return $timing->project->name ?? 'Unknown Project';
        });

        $units = Unit::orderBy('name')->get();

        return view('timing.costume.monitor', compact('runningSessions', 'groupedSessions', 'totalRunning', 'totalFrozen', 'totalEmployees', 'costumeDept', 'units'));
    }

    /**
     * Get employees who clocked in today but have no active timing session (for monitor feed)
     */
    public function getClockedIn()
    {
        $dept = Department::where('name', 'LIKE', '%costume%')
            ->orWhere('name', 'LIKE', '%sewing%')->first();

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
     * Get running sessions via AJAX for auto-refresh
     */
    public function getRunning(TimingBreakService $breakService)
    {
        $breakService->run();

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

        $runningSessions = Timing::whereIn('status', ['on progress', 'frozen'])
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
                $departmentData = $timing->department_specific_data ?? [];
                $isFrozen = $timing->isFrozen();

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
                    'is_frozen' => $isFrozen,
                    'auto_break_paused' => !empty($departmentData['auto_break_paused']),
                    'frozen_duration' => $isFrozen ? ($departmentData['frozen_duration'] ?? '00:00:00') : null,
                    'duration' => $isFrozen
                        ? ($departmentData['frozen_duration'] ?? '00:00:00')
                        : $timing->getDurationAttribute(),
                ];
            }),
            'statistics' => [
                'total_running' => $runningSessions->where('status', 'on progress')->count(),
                'total_frozen' => $runningSessions->where('status', 'frozen')->count(),
                'total_employees' => $runningSessions->unique('employee_id')->count(),
            ],
        ]);
    }
}
