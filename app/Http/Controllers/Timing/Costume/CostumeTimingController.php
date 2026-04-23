<?php

namespace App\Http\Controllers\Timing\Costume;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Timing\ComputesTimingBreak;
use Illuminate\Support\Facades\Auth;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
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

class CostumeTimingController extends Controller
{
    use ComputesTimingBreak;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the costume timer index page
     */
    public function index()
    {
        // Get costume department(s) — includes 'costume', 'sewing', and 'DCM PLUSH'
        $costumeDepts = Department::where(function ($q) {
            $q->where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%')->orWhere('name', 'LIKE', '%DCM PLUSH%');
        })->get();

        $costumeDeptIds = $costumeDepts->pluck('id')->filter()->toArray();
        // Keep single dept for backward compat (used in active session filter)
        $costumeDept = $costumeDepts->first();

        // Only show employees who have clocked in today and NOT yet clocked out
        // ⚠️ Set TIMING_BYPASS_ATTENDANCE=true in .env to bypass attendance check (backup for fingerprint issues)
        $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);
        $clockedInToday = $bypassAttendance ? Employee::where('status', 'active')->whereIn('department_id', $costumeDeptIds)->pluck('id')->toArray() : AttendanceLog::whereDate('date', today())->whereNotNull('clock_in')->whereNull('clock_out')->pluck('employee_id')->toArray();

        // Get employees with running sessions for today
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get active costume employees (exclude those with active sessions, only clocked-in)
        if (!empty($costumeDeptIds)) {
            $employees = Employee::where('status', 'active')
                ->whereIn('department_id', $costumeDeptIds)
                ->whereIn('id', $clockedInToday)
                ->whereNotIn('id', $employeesWithActiveSessions)
                ->with(['department', 'skillsets'])
                ->orderBy('name')
                ->get();

            // Job Orders: filter by department (via pivot or direct) + status != Delivered
            $jobOrders = JobOrder::with(['project', 'department'])
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'Delivered');
                })
                ->where(function ($q) use ($costumeDeptIds) {
                    $q->whereIn('department_id', $costumeDeptIds)->orWhereHas('departments', function ($dq) use ($costumeDeptIds) {
                        $dq->whereIn('departments.id', $costumeDeptIds);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $employees = Employee::where('status', 'active')
                ->whereIn('id', $clockedInToday)
                ->whereNotIn('id', $employeesWithActiveSessions)
                ->with(['department', 'skillsets'])
                ->orderBy('name')
                ->get();

            $jobOrders = JobOrder::with(['project', 'department'])
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'Delivered');
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Group employees by skillset (1 employee bisa muncul di beberapa group)
        $employeesBySkillset = $this->groupEmployeesBySkillset($employees);

        // Get unique departments from active employees
        $departments = Employee::where('status', 'active')->with('department')->get()->pluck('department')->unique('id')->filter()->sortBy('name');

        // Get unique positions from active employees
        $positions = Employee::where('status', 'active')->whereNotNull('position')->distinct()->pluck('position')->sort();

        // Get active timing sessions (ONLY from costume / DCM PLUSH departments, not all)
        $activeSessions = Timing::running()
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($query) use ($costumeDeptIds, $costumeDept) {
                if (!empty($costumeDeptIds)) {
                    $query->whereIn('department_id', $costumeDeptIds);
                }
            })
            ->orderBy('start_time', 'desc')
            ->get();

        // Frozen sessions keyed by employee_id so the view can show paused indicators
        $frozenSessions = Timing::where('status', 'frozen')->today()->withRelations()->get();
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

        return view('timing.costume.index', compact('employees', 'employeesBySkillset', 'jobOrders', 'activeSessions', 'departments', 'positions', 'employeesWithActiveSessions', 'units', 'frozenSessionsByEmployee'));
    }

    /**
     * Ambil skillsets dari tabel skillsets, lalu attach employee yang relevan.
     * Employee tanpa skillset dikumpulkan di group "Other".
     */
    private function groupEmployeesBySkillset($employees): \Illuminate\Support\Collection
    {
        $employeeIds = $employees->pluck('id')->toArray();
        $employeeMap = $employees->keyBy('id');

        $allowedSkillsets = ['Sewing', 'Cutting', 'Embroidery Operation', 'Finishing'];

        $skillsets = Skillset::where('is_active', true)
            ->whereIn('name', $allowedSkillsets)
            ->whereHas('employees', fn($q) => $q->whereIn('employees.id', $employeeIds))
            ->with(['employees' => fn($q) => $q->whereIn('employees.id', $employeeIds)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $groups = $skillsets->map(
            fn($skillset) => [
                'skillset_id' => $skillset->id,
                'label' => $skillset->name,
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
     * Start work session for multiple employees
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'employees' => 'required|array|min:1',
            'employees.*' => 'exists:employees,id',
            'job_order_id' => 'required|exists:job_orders,id',
            'step' => 'required|string|max:255',
            'parts' => 'nullable|string|max:255',
            'session_type' => 'required|in:mass_production,repair',
        ]);

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

                // Employee must have clocked in today (via AttendanceLog OR DailyAttendance)
                // ⚠️ Set TIMING_BYPASS_ATTENDANCE=true in .env to bypass (backup for fingerprint issues)
                $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);
                $hasClockedIn = $bypassAttendance || AttendanceLog::where('employee_id', $employeeId)->whereDate('date', $today)->whereNotNull('clock_in')->exists() || DailyAttendance::where('employee_id', $employeeId)->whereDate('date', $today)->whereNotNull('clock_in')->exists();

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

                // Fingerprint validation: enrolled employees must have tapped IN today
                $fingerprintResult = $this->checkFingerprintTapIn($employee, $today->format('Y-m-d'));
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

                $timing = Timing::create([
                    'tanggal' => $today,
                    'job_order_id' => $validated['job_order_id'],
                    'project_id' => $jobOrder->project_id,
                    'step' => $validated['step'],
                    'parts' => $validated['parts'] ?? 'No Part',
                    'employee_id' => $employeeId,
                    'start_time' => $startTime,
                    'end_time' => null,
                    'measurement_type' => 'pcs', // Default pcs, will be updated on stop
                    'measurement_value' => 0,
                    'status' => 'on progress',
                    'session_type' => $validated['session_type'],
                    'remarks' => null,
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
                    'job_order_deadline' => $jobOrder->deadline ? Carbon::parse($jobOrder->deadline)->format('d M Y') : null,
                    'project_name' => $jobOrder->project->name ?? 'N/A',
                    'step' => $timing->step,
                    'parts' => $timing->parts,
                    'start_time' => $timing->start_time,
                    'session_type' => $timing->session_type,
                    'duration' => '00:00:00',
                ];

                $employeeNames[] = $employee->name;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work started for ' . count($timings) . ' employee(s): ' . implode(', ', $employeeNames),
                'timings' => $timings, // Return individual timings for real-time display
                'start_time' => $startTime,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // In development, throw the exception to see full error details
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
     * Stop work session (INDIVIDUAL - one employee at a time)
     */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'timing_id' => 'required|exists:timings,id', // Single timing ID only
            'output_qty' => 'required|numeric|min:0',
            'measurement_type' => 'required|string|max:50', // Measurement type selection
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work session completed successfully with output: ' . $validated['output_qty'] . ' ' . $validated['measurement_type'],
                'end_time' => $endTime,
                'timing_id' => $timing->id,
                'duration_minutes' => $dur['net'],
                'duration_hours' => round($dur['net'] / 60, 2),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // In development, throw the exception to see full error details
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
     * Bulk stop multiple timing sessions at once (from monitor)
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
        $errors = [];

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
                    'measurement_type' => $validated['measurement_type'],
                    'measurement_value' => $validated['output_qty'],
                    'duration_minutes' => $dur2['net'],
                    'duration_hours' => round($dur2['net'] / 60, 2),
                    'break_deducted_minutes' => $dur2['break'],
                    'status' => 'complete',
                    'approval_status' => 'pending',
                ]);
                $stopped++;
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk stop completed. {$stopped} session(s) stopped" . ($skipped > 0 ? ", {$skipped} skipped (already stopped)." : '.'),
                'stopped' => $stopped,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Bulk stop failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get active sessions via AJAX (individual sessions, not grouped)
     * ONLY for COSTUME department
     */
    public function getActiveSessions(TimingBreakService $breakService)
    {
        $breakService->run();
        // Get costume department
        $costumeDept = Department::where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%')->first();

        if (!$costumeDept) {
            return response()->json([
                'success' => true,
                'sessions' => [], // No costume dept = no sessions
            ]);
        }

        // FILTER by costume department ONLY (include frozen so they stay visible during break)
        $activeSessions = Timing::whereIn('status', ['on progress', 'frozen'])
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($query) use ($costumeDept) {
                $query->where('department_id', $costumeDept->id);
            })
            ->orderBy('start_time', 'desc')
            ->get();

        $sessions = $activeSessions->map(function ($timing) {
            $deptData = $timing->department_specific_data ?? [];
            $isFrozen = $timing->isFrozen();
            return [
                'id' => $timing->id,
                'employee_id' => $timing->employee_id,
                'employee_name' => $timing->employee->name ?? 'N/A',
                'employee_photo' => $timing->employee->photo ?? null,
                'employee_position' => $timing->employee->position ?? 'N/A',
                'job_order_id' => $timing->job_order_id,
                'job_order_name' => $timing->jobOrder->name ?? $timing->job_order_id,
                'project_name' => $timing->project->name ?? 'N/A',
                'step' => $timing->step,
                'parts' => $timing->parts,
                'start_time' => $timing->start_time,
                'status' => $timing->status,
                'is_frozen' => $isFrozen,
                'auto_break_paused' => !empty($deptData['auto_break_paused']),
                'frozen_duration' => $isFrozen ? $deptData['frozen_duration'] ?? '00:00:00' : null,
                'measurement_type' => $timing->measurement_type ?? 'pcs',
                'measurement_value' => $timing->measurement_value ?? 0,
                'duration' => $isFrozen ? $deptData['frozen_duration'] ?? '00:00:00' : $this->calculateDuration($timing->start_time),
            ];
        });

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
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
     * Get available employees for the left panel (excludes those with running sessions).
     * Called via AJAX after start/stop/pause/resume to avoid full page reload.
     */
    public function getAvailableEmployees()
    {
        $costumeDepts = Department::where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%')->orWhere('name', 'LIKE', '%plush%')->get();
        $costumeDeptIds = $costumeDepts->pluck('id')->filter()->toArray();

        $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);
        $clockedInToday = $bypassAttendance ? Employee::where('status', 'active')->whereIn('department_id', $costumeDeptIds)->pluck('id')->toArray() : AttendanceLog::whereDate('date', today())->whereNotNull('clock_in')->pluck('employee_id')->toArray();

        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        $query = Employee::where('status', 'active')
            ->whereIn('id', $clockedInToday)
            ->whereNotIn('id', $employeesWithActiveSessions)
            ->with(['skillsets'])
            ->orderBy('name');

        if (!empty($costumeDeptIds)) {
            $query->whereIn('department_id', $costumeDeptIds);
        }

        $employees = $query->get();

        $frozenSessions = Timing::where('status', 'frozen')->today()->withRelations()->get();
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

    /**
     * Get session information for multiple timing IDs
     * Used for grouped stop modal to show employee details
     */
    public function getSessionsInfo(Request $request)
    {
        $request->validate([
            'timing_ids' => 'required|array',
            'timing_ids.*' => 'exists:timings,id',
        ]);

        $timings = Timing::with(['employee', 'jobOrder', 'project'])
            ->whereIn('id', $request->timing_ids)
            ->whereNull('end_time')
            ->get();

        $sessions = $timings->map(function ($timing) {
            return [
                'id' => $timing->id,
                'employee_name' => $timing->employee->name ?? 'N/A',
                'employee_position' => $timing->employee->position ?? 'N/A',
                'employee_photo' => $timing->employee->photo ?? null,
                'job_order_id' => $timing->job_order_id,
                'job_order_name' => $timing->jobOrder->name ?? $timing->job_order_id,
                'project_name' => $timing->project->name ?? 'N/A',
                'step' => $timing->step,
                'parts' => $timing->parts,
                'start_time' => $timing->start_time,
            ];
        });

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Calculate duration from start time to now
     */
    private function calculateDuration($startTime)
    {
        try {
            $start = Carbon::createFromFormat('H:i:s', $startTime);
            $now = Carbon::now();

            $totalSeconds = $now->diffInSeconds($start);

            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }
}
