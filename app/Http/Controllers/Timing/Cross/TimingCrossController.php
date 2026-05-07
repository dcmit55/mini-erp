<?php

namespace App\Http\Controllers\Timing\Cross;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Timing\ComputesTimingBreak;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dept Cross Timing — universal timing module.
 * No department restriction: any employee, any job order.
 * Data stored in the same `timings` table with source='cross'.
 */
class TimingCrossController extends Controller
{
    use ComputesTimingBreak;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /*  INDEX                                                               */
    /* ──────────────────────────────────────────────────────────────────── */
    public function index()
    {
        $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);

        if ($bypassAttendance) {
            $clockedInToday = Employee::where('status', 'active')->pluck('id')->toArray();
        } else {
            $clockedInToday = AttendanceLog::whereDate('date', today())->whereNotNull('clock_in')->whereNull('clock_out')->pluck('employee_id')->toArray();
        }

        // All active employees (no dept filter)
        $employees = Employee::where('status', 'active')
            ->with(['department', 'skillsets'])
            ->orderBy('name')
            ->get();

        // Employees currently in a running session today
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // All active (non-delivered) job orders
        $jobOrders = JobOrder::with(['project', 'department'])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Delivered');
            })
            ->orderByRaw(
                'CASE
                    WHEN delivery_date IS NULL THEN 2
                    WHEN DATE(delivery_date) < CURDATE() THEN 3
                    ELSE 1
                END ASC',
            )
            ->orderByRaw('CASE WHEN delivery_date IS NOT NULL AND DATE(delivery_date) >= CURDATE() THEN delivery_date END ASC')
            ->orderByRaw('CASE WHEN delivery_date IS NOT NULL AND DATE(delivery_date) < CURDATE() THEN delivery_date END DESC')
            ->get();

        // Active sessions for this module (source='cross') — today
        $activeSessions = Timing::running()->today()->where('source', 'cross')->withRelations()->orderBy('start_time', 'desc')->get();

        // Group employees by department
        $departments = Department::orderBy('name')->get();

        $units = Unit::orderBy('name')->get();

        return view('timing.cross.index', compact('employees', 'employeesWithActiveSessions', 'clockedInToday', 'jobOrders', 'activeSessions', 'departments', 'bypassAttendance', 'units'));
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /*  START                                                               */
    /* ──────────────────────────────────────────────────────────────────── */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'employees' => 'required|array|min:1',
            'employees.*' => 'exists:employees,id',
            'job_order_id' => 'required|exists:job_orders,id',
            'task' => 'nullable|string|max:255',
            'tasks' => 'nullable|array',
            'tasks.*' => 'nullable|string|max:255',
            'session_type' => 'required|in:mass_production,repair',
            'session_types' => 'nullable|array',
            'session_types.*' => 'nullable|in:mass_production,repair',
        ]);

        $taskMap = $validated['tasks'] ?? [];
        $defaultTask = $validated['task'] ?? '';
        $sessionTypeMap = $validated['session_types'] ?? [];
        $defaultSession = $validated['session_type'];

        $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);
        $today = Carbon::now();
        $startTime = $today->format('H:i:s');

        try {
            DB::beginTransaction();

            $jobOrder = JobOrder::with('project')->findOrFail($validated['job_order_id']);

            if (!$jobOrder->project_id) {
                return response()->json(['success' => false, 'message' => 'Job order has no linked project.'], 422);
            }

            $timings = [];
            $employeeNames = [];

            foreach ($validated['employees'] as $employeeId) {
                // Warn (not block) if already has a running session
                $activeSession = Timing::where('employee_id', $employeeId)->where('status', 'on progress')->whereNull('end_time')->whereDate('tanggal', $today)->first();

                if ($activeSession) {
                    // Auto-freeze existing session so new one can start
                    $frozenAt = $today->format('H:i:s');
                    $deptData = $activeSession->department_specific_data ?? [];
                    $pauseLog = $activeSession->pause_log ?? [];
                    $pauseLog[] = ['type' => 'auto', 'paused_at' => $frozenAt, 'resumed_at' => null, 'duration_minutes' => null];
                    $activeSession->update([
                        'status' => 'frozen',
                        'paused_at' => $today,
                        'department_specific_data' => array_merge($deptData, ['frozen_at' => $frozenAt]),
                        'pause_log' => $pauseLog,
                    ]);
                }

                $employee = Employee::find($employeeId);

                $timing = Timing::create([
                    'tanggal' => $today,
                    'job_order_id' => $validated['job_order_id'],
                    'project_id' => $jobOrder->project_id,
                    'step' => $taskMap[$employeeId] ?? $defaultTask,
                    'parts' => null,
                    'employee_id' => $employeeId,
                    'start_time' => $startTime,
                    'end_time' => null,
                    'measurement_type' => 'qty',
                    'measurement_value' => 0,
                    'status' => 'on progress',
                    'session_type' => $sessionTypeMap[$employeeId] ?? $defaultSession,
                    'source' => 'cross',
                    'remarks' => null,
                    'department_specific_data' => ['source' => 'cross'],
                ]);

                $timings[] = [
                    'id' => $timing->id,
                    'employee_id' => $employeeId,
                    'employee_name' => $employee->name,
                    'employee_photo' => $employee->photo,
                    'employee_position' => $employee->position,
                    'job_order_id' => $timing->job_order_id,
                    'job_order_name' => $jobOrder->name,
                    'project_name' => $jobOrder->project->name ?? 'N/A',
                    'task' => $timing->step,
                    'start_time' => $timing->start_time,
                    'session_type' => $timing->session_type,
                    'duration' => '00:00:00',
                ];

                $employeeNames[] = $employee->name;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work session started for: ' . implode(', ', $employeeNames),
                'timings' => $timings,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /*  STOP                                                                */
    /* ──────────────────────────────────────────────────────────────────── */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'timing_id' => 'required|exists:timings,id',
            'stage' => 'required|integer|min:1|max:10',
            'output_qty' => 'required|numeric|min:0',
            'measurement_type' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $timing = Timing::where('id', $validated['timing_id'])->where('status', 'on progress')->whereNull('end_time')->first();

            if (!$timing) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No active session found.'], 422);
            }

            $endTime = now()->format('H:i:s');
            $today = now()->format('Y-m-d');
            $dur = $timing->start_time ? $this->computeTimingDuration($timing, $today, $timing->start_time, $endTime) : ['net' => 0, 'break' => 0];

            $stage = (int) $validated['stage'];
            $currentProgress = $stage * 10;
            $prevDeptData = $timing->department_specific_data ?? [];
            $previousProgress = $prevDeptData['current_progress'] ?? ($prevDeptData['previous_progress'] ?? 0);

            $timing->update([
                'end_time' => $endTime,
                'measurement_type' => $validated['measurement_type'],
                'measurement_value' => $currentProgress,
                'duration_minutes' => $dur['net'],
                'duration_hours' => round($dur['net'] / 60, 2),
                'break_deducted_minutes' => $dur['break'],
                'status' => 'complete',
                'approval_status' => 'pending',
                'remarks' => $validated['remarks'] ?? null,
                'department_specific_data' => array_merge($prevDeptData, [
                    'stage' => $stage,
                    'current_stage' => $stage,
                    'current_progress' => $currentProgress,
                    'previous_progress' => $previousProgress,
                    'output_qty' => $validated['output_qty'],
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session completed.',
                'end_time' => $endTime,
                'timing_id' => $timing->id,
                'duration_minutes' => $dur['net'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /*  FREEZE / UNFREEZE                                                   */
    /* ──────────────────────────────────────────────────────────────────── */
    public function freeze(Request $request)
    {
        $request->validate(['timing_id' => 'required|exists:timings,id']);

        DB::beginTransaction();
        try {
            $timing = Timing::where('id', $request->timing_id)->where('status', 'on progress')->whereNull('end_time')->first();

            if (!$timing) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No active session found.'], 422);
            }

            $frozenAt = now()->format('H:i:s');
            $today = now()->format('Y-m-d');

            $frozenDuration = '00:00:00';
            if ($timing->start_time) {
                $start = Carbon::parse($today . ' ' . $timing->start_time);
                $end = Carbon::parse($today . ' ' . $frozenAt);
                $diff = $start->diff($end);
                $frozenDuration = sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
            }

            $deptData = $timing->department_specific_data ?? [];
            $deptData['frozen_at'] = $frozenAt;
            $deptData['frozen_duration'] = $frozenDuration;

            $pauseLog = $timing->pause_log ?? [];
            $pauseLog[] = ['type' => 'manual', 'paused_at' => $frozenAt, 'resumed_at' => null, 'duration_minutes' => null];

            $timing->update([
                'status' => 'frozen',
                'paused_at' => now(),
                'department_specific_data' => $deptData,
                'pause_log' => $pauseLog,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Session paused.', 'frozen_duration' => $frozenDuration]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function unfreeze(Request $request)
    {
        $request->validate(['timing_id' => 'required|exists:timings,id']);

        DB::beginTransaction();
        try {
            $timing = Timing::where('id', $request->timing_id)->where('status', 'frozen')->whereNull('end_time')->first();

            if (!$timing) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No paused session found.'], 422);
            }

            $deptData = $timing->department_specific_data ?? [];
            $frozenDuration = $deptData['frozen_duration'] ?? '00:00:00';

            [$h, $m, $s] = array_map('intval', explode(':', $frozenDuration));
            $newStartTime = now()
                ->subSeconds($h * 3600 + $m * 60 + $s)
                ->format('H:i:s');
            $pausedMins = $timing->paused_at ? (int) $timing->paused_at->diffInMinutes(now()) : 0;

            unset($deptData['frozen_at'], $deptData['frozen_duration']);

            $pauseLog = $timing->pause_log ?? [];
            foreach (array_reverse(array_keys($pauseLog)) as $i) {
                if ($pauseLog[$i]['resumed_at'] === null) {
                    $pauseLog[$i]['resumed_at'] = now()->format('H:i:s');
                    $pauseLog[$i]['duration_minutes'] = $pausedMins;
                    break;
                }
            }

            $timing->update([
                'status' => 'on progress',
                'start_time' => $newStartTime,
                'paused_at' => null,
                'total_paused_minutes' => ($timing->total_paused_minutes ?? 0) + $pausedMins,
                'department_specific_data' => $deptData,
                'pause_log' => $pauseLog,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Session resumed.', 'new_start_time' => $newStartTime]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /*  ACTIVE SESSIONS (AJAX)                                              */
    /* ──────────────────────────────────────────────────────────────────── */
    public function getActiveSessions()
    {
        $sessions = Timing::running()->today()->where('source', 'cross')->withRelations()->orderBy('start_time', 'desc')->get();

        return response()->json([
            'success' => true,
            'sessions' => $sessions->map(function ($t) {
                $deptData = $t->department_specific_data ?? [];
                $isFrozen = $t->status === 'frozen';
                return [
                    'id' => $t->id,
                    'employee_id' => $t->employee_id,
                    'employee_name' => $t->employee->name ?? '-',
                    'employee_photo' => $t->employee->photo ?? null,
                    'employee_position' => $t->employee->position ?? null,
                    'job_order_id' => $t->job_order_id,
                    'job_order_name' => $t->jobOrder->name ?? '-',
                    'project_name' => $t->project->name ?? '-',
                    'task' => $t->step ?? '-',
                    'start_time' => $t->start_time,
                    'status' => $t->status,
                    'session_type' => $t->session_type,
                    'is_frozen' => $isFrozen,
                    'frozen_duration' => $deptData['frozen_duration'] ?? '00:00:00',
                    'previous_progress' => $deptData['current_progress'] ?? ($deptData['previous_progress'] ?? 0),
                    'previous_stage' => $deptData['current_stage'] ?? ($deptData['stage'] ?? 0),
                    'auto_break_paused' => false,
                ];
            }),
        ]);
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /*  BULK STOP                                                           */
    /* ──────────────────────────────────────────────────────────────────── */
    public function bulkStop(Request $request)
    {
        $validated = $request->validate([
            'timing_ids' => 'required|array|min:1',
            'timing_ids.*' => 'exists:timings,id',
            'output_qty' => 'required|numeric|min:0',
            'measurement_type' => 'required|string|max:50',
        ]);

        $stopped = 0;
        $skipped = 0;
        $endTime = now()->format('H:i:s');
        $today = now()->format('Y-m-d');

        DB::beginTransaction();
        try {
            foreach ($validated['timing_ids'] as $timingId) {
                $timing = Timing::where('id', $timingId)->where('status', 'on progress')->whereNull('end_time')->first();

                if (!$timing) {
                    $skipped++;
                    continue;
                }

                $dur = $timing->start_time ? $this->computeTimingDuration($timing, $today, $timing->start_time, $endTime) : ['net' => 0, 'break' => 0];

                $timing->update([
                    'end_time' => $endTime,
                    'measurement_type' => $validated['measurement_type'],
                    'measurement_value' => $validated['output_qty'],
                    'duration_minutes' => $dur['net'],
                    'duration_hours' => round($dur['net'] / 60, 2),
                    'break_deducted_minutes' => $dur['break'],
                    'status' => 'complete',
                    'approval_status' => 'pending',
                ]);
                $stopped++;
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$stopped} session(s) stopped" . ($skipped > 0 ? ", {$skipped} skipped." : '.'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
