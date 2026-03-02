<?php

namespace App\Http\Controllers\Timing;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // Get mascot timing running count
        $mascotRunning = Timing::running()
            ->today()
            ->whereHas('employee.department', function ($query) {
                $query->where('name', 'LIKE', '%Mascot%');
            })
            ->count();

        return view('timing.monitor.index', compact('runningSessions', 'totalRunning', 'totalEmployees', 'costumeRunning', 'animatronicsRunning', 'mascotRunning'));
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
                'mascot_running' => Timing::running()
                    ->today()
                    ->whereHas('employee.department', function ($query) {
                        $query->where('name', 'LIKE', '%Mascot%');
                    })
                    ->count(),
            ],
        ]);
    }

    /**
     * Stop timing session from monitor (quick stop without output)
     */
    public function stopSession(Request $request)
    {
        $validated = $request->validate([
            'timing_id' => 'required|exists:timings,id',
        ]);

        try {
            DB::beginTransaction();

            $timing = Timing::where('id', $validated['timing_id'])->where('status', 'on progress')->whereNull('end_time')->first();

            if (!$timing) {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Active session not found.',
                    ],
                    422,
                );
            }

            $endTime = now()->format('H:i:s');

            // Calculate duration in minutes
            $durationMinutes = 0;
            if ($timing->start_time && $endTime) {
                try {
                    $today = now()->format('Y-m-d');
                    $start = Carbon::parse($today . ' ' . $timing->start_time);
                    $end = Carbon::parse($today . ' ' . $endTime);
                    $durationMinutes = $start->diffInMinutes($end);
                } catch (\Exception $e) {
                    $durationMinutes = 0;
                }
            }

            // Update timing - set to complete with default values
            $timing->update([
                'end_time' => $endTime,
                'duration_minutes' => $durationMinutes,
                'duration_hours' => round($durationMinutes / 60, 2),
                'status' => 'complete',
                'measurement_value' => $timing->measurement_value ?? 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session stopped successfully.',
                'timing_id' => $timing->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to stop session: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get available employees (not running)
     */
    public function getAvailableEmployees()
    {
        // Get employees with active sessions today
        $runningEmployeeIds = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get all active employees NOT in running sessions
        $availableEmployees = \App\Models\Hr\Employee::where('status', 'active')
            ->whereNotIn('id', $runningEmployeeIds)
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'position' => $employee->position,
                    'photo' => $employee->photo,
                    'department' => $employee->department->name ?? 'Unknown',
                ];
            });

        return response()->json([
            'success' => true,
            'employees' => $availableEmployees,
            'total' => $availableEmployees->count(),
        ]);
    }
}
