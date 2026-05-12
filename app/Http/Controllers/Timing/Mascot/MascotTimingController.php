<?php

namespace App\Http\Controllers\Timing\Mascot;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Timing\ComputesTimingBreak;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Production\JobOrderTimingPlan;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Skillset;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use App\Services\Timing\TimingBreakService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MascotTimingController extends Controller
{
    use ComputesTimingBreak;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the mascot timer index page
     */
    public function index()
    {
        // Get Mascot department
        $mascotDept = Department::where('name', 'LIKE', '%Mascot%')->first();

        if (!$mascotDept) {
            return redirect()->route('dashboard')->with('error', 'Mascot department not found. Please contact administrator.');
        }

        // Only show employees who have clocked in today and NOT yet clocked out
        // ⚠️ Set TIMING_BYPASS_ATTENDANCE=true in .env to bypass attendance check (dev/ops use)
        $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);

        if ($bypassAttendance) {
            $clockedInToday = Employee::where('status', 'active')->where('department_id', $mascotDept->id)->pluck('id')->toArray();
        } else {
            $clockedInToday = AttendanceLog::whereDate('date', today())->whereNotNull('clock_in')->whereNull('clock_out')->pluck('employee_id')->toArray();
        }

        // Get employees with running sessions for today
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get active mascot employees (only clocked-in, exclude those with active sessions)
        $employees = Employee::where('status', 'active')
            ->where('department_id', $mascotDept->id)
            ->whereIn('id', $clockedInToday)
            ->whereNotIn('id', $employeesWithActiveSessions)
            ->with(['department', 'skillsets'])
            ->orderBy('name')
            ->get();

        // Group employees by skillset — dengan rename khusus Mascot
        $employeesBySkillset = $this->groupEmployeesBySkillset($employees);

        // Get Mascot + Animatronics department IDs (shared workload between these departments)
        $sharedDepts = Department::where(function ($q) {
            $q->where('name', 'LIKE', '%mascot%')->orWhere('name', 'LIKE', '%animatronic%')->orWhere('name', 'LIKE', '%animation%');
        })
            ->pluck('id')
            ->toArray();

        // Job Orders: filter by Mascot/Animatronics department (via pivot or direct) + status != Delivered
        // Sorted: DUE TODAY first, then upcoming by date, OVERDUE last, nulls last
        $jobOrders = JobOrder::with(['project', 'department'])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Delivered');
            })
            ->where(function ($q) use ($sharedDepts) {
                $q->whereIn('department_id', $sharedDepts)->orWhereHas('departments', function ($dq) use ($sharedDepts) {
                    $dq->whereIn('departments.id', $sharedDepts);
                });
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

        // Planned employees per JO (from timing planner) — PRIORITY 1
        $joIds = $jobOrders->pluck('id')->toArray();
        $plannedEmployeesPerJo = [];
        $plannedDataPerJo = []; // includes stage & session_type
        if (!empty($joIds)) {
            $today = today()->toDateString();
            // Load today's date-scoped plans first; fall back to NULL (legacy) plans for uncovered JOs
            $todayPlans = JobOrderTimingPlan::whereIn('job_order_id', $joIds)->where('planning_date', $today)->select('job_order_id', 'employee_id', 'task', 'parts', 'stage', 'session_type', 'planning_date', 'updated_at')->get()->groupBy('job_order_id');
            $coveredJoIds = $todayPlans->keys()->toArray();
            $uncoveredJoIds = array_diff($joIds, $coveredJoIds);
            $legacyPlans = !empty($uncoveredJoIds) ? JobOrderTimingPlan::whereIn('job_order_id', $uncoveredJoIds)->whereNull('planning_date')->select('job_order_id', 'employee_id', 'task', 'parts', 'stage', 'session_type', 'planning_date', 'updated_at')->get()->groupBy('job_order_id') : collect();
            $plansByJo = $todayPlans->union($legacyPlans);
            foreach ($plansByJo as $joId => $rows) {
                $plannedEmployeesPerJo[$joId] = $rows->pluck('employee_id')->toArray();
                $first = $rows->first();
                $plannedDataPerJo[$joId] = [
                    'employee_ids' => $rows->pluck('employee_id')->toArray(),
                    'task' => $first->task ?? '',
                    'task_per_emp' => $rows->pluck('task', 'employee_id')->toArray(),
                    'parts_per_emp' => $rows->pluck('parts', 'employee_id')->toArray(),
                    'stage' => $first->stage ?? '',
                    'stage_per_emp' => $rows->pluck('stage', 'employee_id')->toArray(),
                    'session_type' => $first->session_type ?? '',
                    'session_type_per_emp' => $rows->pluck('session_type', 'employee_id')->toArray(),
                    'plan_updated_at' => $first->updated_at ? $first->updated_at->format('d M H:i') : null,
                    'planning_date' => $first->planning_date,
                ];
            }
        }

        // Last employees per job order (from most recent timing session) — PRIORITY 2 (fallback)
        $joIds = $jobOrders->pluck('id')->toArray();
        $lastEmployeesPerJo = [];
        if (!empty($joIds)) {
            $lastSessionsSubq = Timing::whereIn('job_order_id', $joIds)->selectRaw('job_order_id, MAX(DATE(tanggal)) as last_date')->groupBy('job_order_id');
            $lastSessionEmployees = Timing::joinSub($lastSessionsSubq, 'lsd', function ($join) {
                $join->on('timings.job_order_id', '=', 'lsd.job_order_id')->whereRaw('DATE(timings.tanggal) = lsd.last_date');
            })
                ->whereIn('timings.job_order_id', $joIds)
                ->select('timings.job_order_id', 'timings.employee_id')
                ->distinct()
                ->get();
            $lastEmployeesPerJo = $lastSessionEmployees->groupBy('job_order_id')->map(fn($rows) => $rows->pluck('employee_id')->toArray())->toArray();
        }

        // Get active timing sessions for mascot
        $activeSessions = Timing::running()
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($query) use ($mascotDept) {
                $query->where('department_id', $mascotDept->id);
            })
            ->orderBy('start_time', 'desc')
            ->get();

        // Get positions in mascot dept
        $positions = Employee::where('status', 'active')->where('department_id', $mascotDept->id)->whereNotNull('position')->distinct()->pluck('position')->sort();

        // Frozen sessions keyed by employee_id so the view can show paused indicators
        $frozenSessions = Timing::where('status', 'frozen')
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($q) use ($mascotDept) {
                $q->where('department_id', $mascotDept->id);
            })
            ->get();
        $frozenSessionsByEmployee = $frozenSessions
            ->keyBy('employee_id')
            ->map(function ($t) {
                $deptData = $t->department_specific_data ?? [];
                return [
                    'timing_id' => $t->id,
                    'job_order_name' => $t->jobOrder->name ?? 'N/A',
                    'frozen_duration' => $deptData['frozen_duration'] ?? '00:00:00',
                ];
            })
            ->toArray();

        $units = Unit::orderBy('name')->get();

        return view('timing.mascot.index', compact('employees', 'employeesBySkillset', 'jobOrders', 'activeSessions', 'mascotDept', 'positions', 'employeesWithActiveSessions', 'units', 'frozenSessionsByEmployee', 'lastEmployeesPerJo', 'plannedEmployeesPerJo', 'plannedDataPerJo'));
    }

    // Rename khusus Mascot
    private const SKILLSET_LABELS = [
        45 => 'Inflatable',
        3 => 'FRP',
    ];

    /**
     * Group employees by skillset dengan rename khusus Mascot.
     */
    private function groupEmployeesBySkillset($employees): \Illuminate\Support\Collection
    {
        $employeeIds = $employees->pluck('id')->toArray();
        $employeeMap = $employees->keyBy('id');

        $skillsets = Skillset::where('is_active', true)
            ->whereHas('employees', fn($q) => $q->whereIn('employees.id', $employeeIds))
            ->with(['employees' => fn($q) => $q->whereIn('employees.id', $employeeIds)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $groups = $skillsets->map(
            fn($skillset) => [
                'skillset_id' => $skillset->id,
                'label' => self::SKILLSET_LABELS[$skillset->id] ?? $skillset->name,
                'employees' => $skillset->employees->map(
                    fn($emp) => [
                        'employee' => $employeeMap->get($emp->id) ?? $emp,
                    ],
                ),
            ],
        );

        $assignedIds = $skillsets->flatMap(fn($s) => $s->employees->pluck('id'))->unique();
        $unassigned = $employees->whereNotIn('id', $assignedIds);

        if ($unassigned->isNotEmpty()) {
            $groups->push([
                'skillset_id' => null,
                'label' => 'Other',
                'employees' => $unassigned->map(fn($emp) => ['employee' => $emp]),
            ]);
        }

        return $groups->values();
    }

    /**
     * Start work session for mascot employees
     */
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
            'parts' => 'nullable|array',
            'parts.*' => 'nullable|string|max:100',
            'stages' => 'nullable|array',
            'stages.*' => 'nullable|string|max:100',
        ]);

        // Build per-employee task map: tasks[emp_id] overrides task global
        $taskMap = $validated['tasks'] ?? [];
        $defaultTask = $validated['task'] ?? 'N/A';
        // Build per-employee session_type map: session_types[emp_id] overrides global session_type
        $sessionTypeMap = $validated['session_types'] ?? [];
        $defaultSessionType = $validated['session_type'];
        // Build per-employee parts map
        $partsMap = $validated['parts'] ?? [];
        // Build per-employee planned stage map (string like "5: Adjustment...")
        $stageMap = $validated['stages'] ?? [];

        try {
            DB::beginTransaction();

            // Get job order with project
            $jobOrder = JobOrder::with('project')->findOrFail($validated['job_order_id']);

            if (!$jobOrder->project_id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Selected Job Order does not have a linked Project.',
                    ],
                    422,
                );
            }

            $startTime = now()->format('H:i:s');
            $today = today();

            $timings = [];
            $employeeNames = [];

            foreach ($validated['employees'] as $employeeId) {
                // Check if employee already has active session
                $activeSession = Timing::where('employee_id', $employeeId)->where('status', 'on progress')->whereNull('end_time')->whereDate('tanggal', $today)->first();

                if ($activeSession) {
                    $employee = Employee::find($employeeId);
                    DB::rollBack();
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Employee {$employee->name} already has an active work session. Please stop it first.",
                        ],
                        422,
                    );
                }

                // Employee must have clocked in today and NOT yet clocked out
                // ⚠️ Set TIMING_BYPASS_ATTENDANCE=true in .env to bypass attendance check (backup for fingerprint issues)
                $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);
                $hasClockedIn = $bypassAttendance || AttendanceLog::where('employee_id', $employeeId)->whereDate('date', $today)->whereNotNull('clock_in')->whereNull('clock_out')->exists() || DailyAttendance::where('employee_id', $employeeId)->whereDate('date', $today)->whereNotNull('clock_in')->whereNull('clock_out')->exists();

                if (!$hasClockedIn) {
                    $employee = Employee::find($employeeId);
                    DB::rollBack();
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Employee {$employee->name} has not clocked in today. Cannot start timing session.",
                        ],
                        422,
                    );
                }

                $employee = Employee::find($employeeId);

                // Fingerprint validation — also bypassed in testing mode
                $fingerprintResult = $bypassAttendance ? true : $this->checkFingerprintTapIn($employee, $today->format('Y-m-d'));
                if ($fingerprintResult === false) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Employee {$employee->name} has not tapped in on the fingerprint machine today. Cannot start timing session.",
                        ],
                        422,
                    );
                }

                // Get previous stage for this job order (shared across all employees)
                $lastTiming = Timing::where('job_order_id', $validated['job_order_id'])
                    ->whereNotNull('department_specific_data')
                    ->whereNotNull('end_time') // Only completed sessions
                    ->latest('tanggal')
                    ->latest('end_time')
                    ->first();

                $previousStage = 0;
                $previousProgress = 0;
                if ($lastTiming && isset($lastTiming->department_specific_data['stage'])) {
                    $previousStage = $lastTiming->department_specific_data['stage'];
                    $previousProgress = $lastTiming->department_specific_data['current_progress'] ?? $previousStage * 10;
                }

                // Prepare department-specific data for mascot
                $deptSpecificData = [
                    'tracking_mode' => 'stage_progress', // Mascot uses stage-based progress
                    'previous_stage' => $previousStage,
                    'previous_progress' => $previousProgress,
                    'current_stage' => $previousStage, // Will be updated on stop
                    'current_progress' => $previousProgress, // Will be updated on stop
                    'planned_stage' => $stageMap[$employeeId] ?? null, // From Timing Planner
                ];

                $timing = Timing::create([
                    'tanggal' => $today,
                    'job_order_id' => $validated['job_order_id'],
                    'project_id' => $jobOrder->project_id,
                    'step' => $taskMap[$employeeId] ?? $defaultTask, // Per-employee task
                    'parts' => $partsMap[$employeeId] ?? null, // Per-employee parts
                    'employee_id' => $employeeId,
                    'start_time' => $startTime,
                    'end_time' => null,
                    'measurement_type' => 'percentage', // Stage-based progress (use percentage from enum)
                    'measurement_value' => 0, // Will be set on stop
                    'status' => 'on progress',
                    'session_type' => $sessionTypeMap[$employeeId] ?? $defaultSessionType, // Per-employee session_type
                    'remarks' => null,
                    'department_specific_data' => $deptSpecificData,
                ]);

                // Return full timing data for real-time display
                $timings[] = [
                    'id' => $timing->id,
                    'employee_id' => $employeeId,
                    'employee_name' => $employee->name,
                    'employee_photo' => $employee->photo,
                    'employee_position' => $employee->position,
                    'job_order_id' => $timing->job_order_id,
                    'job_order_name' => $jobOrder->name,
                    'job_order_deadline' => $jobOrder->deadline ? \Carbon\Carbon::parse($jobOrder->deadline)->format('d M Y') : null,
                    'project_name' => $jobOrder->project->name ?? 'N/A',
                    'task' => $timing->step,
                    'start_time' => $timing->start_time,
                    'session_type' => $timing->session_type,
                    'duration' => '00:00:00',
                    'previous_stage' => $previousStage,
                    'previous_progress' => $previousProgress,
                ];

                $employeeNames[] = $employee->name;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work started for ' . count($timings) . ' employee(s): ' . implode(', ', $employeeNames),
                'timings' => $timings,
                'start_time' => $startTime,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if (config('app.debug')) {
                throw $e;
            }

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to start work: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Stop work session — requires stage (integer 1-10).
     * Stage 1=Design & Prototyping ... Stage 10=Final QC & Shipping.
     * Each stage = 10% absolute progress. Stored in department_specific_data.
     */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'timing_id' => 'required|exists:timings,id',
            'stage' => 'required|integer|min:1|max:10', // Stage 1-10: each = 10% progress
            'output_qty' => 'required|numeric|min:0',
            'measurement_type' => 'required|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            $endTime = now()->format('H:i:s');

            $timing = Timing::where('id', $validated['timing_id'])->where('status', 'on progress')->whereNull('end_time')->first();

            if (!$timing) {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No active work session found to stop.',
                    ],
                    422,
                );
            }

            // Calculate net duration in minutes (break time excluded)
            $today = now()->format('Y-m-d');
            $dur = $timing->start_time ? $this->computeTimingDuration($timing, $today, $timing->start_time, $endTime) : ['net' => 0, 'break' => 0];

            // Get existing department-specific data
            $deptSpecificData = $timing->department_specific_data ?? [];
            $previousProgress = $deptSpecificData['previous_progress'] ?? 0;

            // Absolute progress: stage 2 = 20%, stage 5 = 50%, etc.
            $stage = $validated['stage'];
            $currentProgress = $stage * 10;
            $progressAdded = $currentProgress - $previousProgress;

            // Update department-specific data
            $deptSpecificData['current_stage'] = $stage;
            $deptSpecificData['current_progress'] = $currentProgress;
            $deptSpecificData['progress_added'] = $progressAdded;
            $deptSpecificData['stage'] = $stage;

            // Update timing record
            $timing->update([
                'end_time' => $endTime,
                'measurement_type' => 'percentage',
                'measurement_value' => $currentProgress,
                'duration_minutes' => $dur['net'],
                'duration_hours' => round($dur['net'] / 60, 2),
                'break_deducted_minutes' => $dur['break'],
                'status' => 'complete',
                'approval_status' => 'pending',
                'department_specific_data' => $deptSpecificData,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Work session completed. Stage {$stage} reached ({$currentProgress}% progress).",
                'stage' => $stage,
                'current_progress' => $currentProgress,
                'progress_added' => $progressAdded,
                'end_time' => $endTime,
                'timing_id' => $timing->id,
                'duration_minutes' => $dur['net'],
                'duration_hours' => round($dur['net'] / 60, 2),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if (config('app.debug')) {
                throw $e;
            }

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to stop work: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Bulk stop multiple mascot timing sessions (from monitor)
     * Sets default qty=1, measurement=pcs for each session.
     */
    public function bulkStop(Request $request)
    {
        $validated = $request->validate([
            'timing_ids' => 'required|array|min:1',
            'timing_ids.*' => 'required|exists:timings,id',
            'measurement_type' => 'required|string|max:50',
            'output_qty' => 'required|numeric|min:0',
        ]);

        $endTime = now()->format('H:i:s');
        $stopped = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['timing_ids'] as $timingId) {
                $timing = Timing::where('id', $timingId)->where('status', 'on progress')->whereNull('end_time')->first();

                if (!$timing) {
                    $skipped++;
                    continue;
                }

                $today2 = now()->format('Y-m-d');
                $dur2 = $timing->start_time ? $this->computeTimingDuration($timing, $today2, $timing->start_time, $endTime) : ['net' => 0, 'break' => 0];

                $timing->update([
                    'end_time' => $endTime,
                    'measurement_type' => 'percentage',
                    'measurement_value' => 0,
                    'duration_minutes' => $dur2['net'],
                    'duration_hours' => round($dur2['net'] / 60, 2),
                    'break_deducted_minutes' => $dur2['break'],
                    'status' => 'complete',
                    'approval_status' => 'pending',
                ]);
                $stopped++;
            } // end foreach

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk stop completed. {$stopped} session(s) stopped" . ($skipped > 0 ? ", {$skipped} skipped (no stage saved or already stopped)." : '.'),
                'stopped' => $stopped,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Bulk stop failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Freeze (manually pause) an active session — timer stops, session stays open.
     */
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

            // Append pause event to log
            $pauseLog = $timing->pause_log ?? [];
            $pauseLog[] = ['type' => 'manual', 'paused_at' => $frozenAt, 'resumed_at' => null, 'duration_minutes' => null];

            $timing->update([
                'status' => 'frozen',
                'paused_at' => now(),
                'department_specific_data' => $deptData,
                'pause_log' => $pauseLog,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Session paused.',
                'frozen_duration' => $frozenDuration,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to pause: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unfreeze (resume) a frozen session — adjusts start_time so elapsed time is preserved.
     */
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

            // Auto-freeze any other running session for this employee so only one runs at a time
            $autoFrozeName = null;
            $otherRunning = Timing::where('employee_id', $timing->employee_id)
                ->where('id', '!=', $timing->id)
                ->whereIn('status', ['on progress', 'running'])
                ->whereNull('end_time')
                ->whereDate('tanggal', today())
                ->first();

            if ($otherRunning) {
                $nowStr = now()->format('H:i:s');
                $todayStr = now()->format('Y-m-d');
                $otherData = $otherRunning->department_specific_data ?? [];
                $frozenDur = '00:00:00';
                if ($otherRunning->start_time) {
                    $s2 = Carbon::parse($todayStr . ' ' . $otherRunning->start_time);
                    $e2 = Carbon::parse($todayStr . ' ' . $nowStr);
                    if ($e2->lt($s2)) {
                        $e2->addDay();
                    }
                    $diff2 = $s2->diff($e2);
                    $frozenDur = sprintf('%02d:%02d:%02d', $diff2->h + $diff2->days * 24, $diff2->i, $diff2->s);
                }
                $otherData['frozen_at'] = $nowStr;
                $otherData['frozen_duration'] = $frozenDur;
                $otherPauseLog = $otherRunning->pause_log ?? [];
                $otherPauseLog[] = ['type' => 'manual', 'paused_at' => $nowStr, 'resumed_at' => null, 'duration_minutes' => null];
                $otherRunning->update([
                    'status' => 'frozen',
                    'paused_at' => now(),
                    'department_specific_data' => $otherData,
                    'pause_log' => $otherPauseLog,
                ]);
                $autoFrozeName = $otherRunning->jobOrder->name ?? 'Session #' . $otherRunning->id;
            }

            $deptData = $timing->department_specific_data ?? [];
            $frozenDuration = $deptData['frozen_duration'] ?? '00:00:00';

            [$h, $m, $s] = array_map('intval', explode(':', $frozenDuration));
            $newStartTime = now()
                ->subSeconds($h * 3600 + $m * 60 + $s)
                ->format('H:i:s');
            $pausedMins = $timing->paused_at ? (int) $timing->paused_at->diffInMinutes(now()) : 0;
            $resumedAt = now()->format('H:i:s');

            unset($deptData['frozen_at'], $deptData['frozen_duration'], $deptData['auto_break_paused']);

            // Update last open pause log entry with resume time
            $pauseLog = $timing->pause_log ?? [];
            if (!empty($pauseLog)) {
                $last = &$pauseLog[count($pauseLog) - 1];
                if ($last['resumed_at'] === null) {
                    $last['resumed_at'] = $resumedAt;
                    $last['duration_minutes'] = $pausedMins;
                }
            }

            $timing->update([
                'status' => 'on progress',
                'start_time' => $newStartTime,
                'paused_at' => null,
                'total_paused_minutes' => ($timing->total_paused_minutes ?? 0) + $pausedMins,
                'department_specific_data' => $deptData ?: null,
                'pause_log' => $pauseLog ?: null,
            ]);

            DB::commit();

            $message = 'Session resumed.';
            if ($autoFrozeName) {
                $message .= " \"{$autoFrozeName}\" was auto-paused.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'new_start_time' => $newStartTime,
                'auto_froze' => $autoFrozeName,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to resume: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get active sessions via AJAX (individual sessions)
     */
    public function getActiveSessions(TimingBreakService $breakService)
    {
        $breakService->run();

        $mascotDept = Department::where('name', 'LIKE', '%Mascot%')->first();

        if (!$mascotDept) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Mascot department not found.',
                ],
                404,
            );
        }

        // Include frozen sessions so cards stay visible during auto-break
        $activeSessions = Timing::whereIn('status', ['on progress', 'frozen'])
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($query) use ($mascotDept) {
                $query->where('department_id', $mascotDept->id);
            })
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($timing) {
                $deptData = $timing->department_specific_data ?? [];
                $isFrozen = $timing->isFrozen();

                $durationSeconds = 0;
                if ($timing->start_time && !$isFrozen) {
                    $start = Carbon::parse($timing->tanggal->format('Y-m-d') . ' ' . $timing->start_time);
                    $durationSeconds = $start->diffInSeconds(now());
                }

                $previousStage = $deptData['previous_stage'] ?? 0;
                $previousProgress = $deptData['previous_progress'] ?? 0;

                return [
                    'id' => $timing->id,
                    'employee_id' => $timing->employee_id,
                    'employee_name' => $timing->employee->name ?? 'N/A',
                    'employee_photo' => $timing->employee->photo ?? null,
                    'employee_position' => $timing->employee->position ?? 'N/A',
                    'job_order_id' => $timing->job_order_id,
                    'job_order_name' => $timing->jobOrder->name ?? 'N/A',
                    'project_name' => $timing->project->name ?? 'N/A',
                    'task' => $timing->step,
                    'start_time' => $timing->start_time,
                    'status' => $timing->status,
                    'is_frozen' => $isFrozen,
                    'auto_break_paused' => !empty($deptData['auto_break_paused']),
                    'frozen_duration' => $isFrozen ? $deptData['frozen_duration'] ?? '00:00:00' : null,
                    'duration_seconds' => $durationSeconds,
                    'previous_stage' => $previousStage,
                    'previous_progress' => $previousProgress,
                    'session_type' => $timing->session_type ?? 'mass_production',
                ];
            });

        return response()->json([
            'success' => true,
            'sessions' => $activeSessions,
        ]);
    }

    /**
     * Get job order info for display
     */
    public function getJobOrderInfo($jobOrderId)
    {
        $jobOrder = JobOrder::with(['project', 'department'])->find($jobOrderId);

        if (!$jobOrder) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Job Order not found.',
                ],
                404,
            );
        }

        // Get latest stage for this job order (include in-progress sessions too)
        $lastTiming = Timing::where('job_order_id', $jobOrderId)->whereNotNull('department_specific_data')->latest('tanggal')->latest('updated_at')->first();

        $currentStage = 0;
        $currentProgress = 0;
        if ($lastTiming && isset($lastTiming->department_specific_data['stage'])) {
            $currentStage = $lastTiming->department_specific_data['stage'];
            $currentProgress = $lastTiming->department_specific_data['current_progress'] ?? $currentStage * 10;
        }

        return response()->json([
            'success' => true,
            'job_order' => [
                'id' => $jobOrder->id,
                'name' => $jobOrder->name,
                'project_name' => $jobOrder->project->name ?? 'N/A',
                'department_name' => $jobOrder->department->name ?? 'N/A',
                'status' => $jobOrder->status ?? null,
                'current_stage' => $currentStage,
                'current_progress' => $currentProgress,
            ],
        ]);
    }

    /**
     * Get available employees for the left panel (AJAX — avoids full page reload).
     */
    public function getAvailableEmployees()
    {
        $mascotDept = Department::where('name', 'LIKE', '%Mascot%')->first();

        $clockedInToday = AttendanceLog::whereDate('date', today())->whereNotNull('clock_in')->whereNull('clock_out')->pluck('employee_id')->toArray();

        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        $query = Employee::where('status', 'active')
            ->whereIn('id', $clockedInToday)
            ->whereNotIn('id', $employeesWithActiveSessions)
            ->with(['skillsets'])
            ->orderBy('name');

        if ($mascotDept) {
            $query->where('department_id', $mascotDept->id);
        }

        $employees = $query->get();

        $frozenSessions = Timing::where('status', 'frozen')->today()->withRelations()->get();
        if ($mascotDept) {
            $frozenSessions = $frozenSessions->filter(fn($t) => $t->employee?->department_id === $mascotDept->id);
        }
        $frozenMap = $frozenSessions
            ->keyBy('employee_id')
            ->map(function ($t) {
                $d = $t->department_specific_data ?? [];
                return [
                    'timing_id' => $t->id,
                    'job_order_name' => $t->jobOrder->name ?? 'N/A',
                    'frozen_duration' => $d['frozen_duration'] ?? '00:00:00',
                ];
            })
            ->toArray();

        $data = $employees->map(
            fn($emp) => [
                'id' => $emp->id,
                'name' => $emp->name,
                'photo' => $emp->photo,
                'position' => $emp->position,
                'department_id' => $emp->department_id,
                'skillset_ids' => $emp->skillsets->pluck('id')->toArray(),
                'frozen_info' => $frozenMap[$emp->id] ?? null,
            ],
        );

        return response()->json([
            'success' => true,
            'employees' => $data,
            'frozen_sessions_by_employee' => $frozenMap,
        ]);
    }
}
