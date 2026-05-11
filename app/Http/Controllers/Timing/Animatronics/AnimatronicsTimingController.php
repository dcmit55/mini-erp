<?php

namespace App\Http\Controllers\Timing\Animatronics;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Timing\ComputesTimingBreak;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use App\Services\DepartmentTimingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AnimatronicsTimingController extends Controller
{
    use ComputesTimingBreak;
    protected $deptTimingService;

    public function __construct(DepartmentTimingService $service)
    {
        $this->middleware('auth');
        $this->deptTimingService = $service;
    }

    /**
     * Display the animatronics timer index page
     */
    public function index()
    {
        // Get animatronics department
        $animatronicsDept = Department::where('name', 'LIKE', '%animatronic%')->orWhere('name', 'LIKE', '%animation%')->first();

        if (!$animatronicsDept) {
            return redirect()->route('costume-timing.index')->with('error', 'Animatronics department not found. Please use costume timing.');
        }

        // Only show employees who have clocked in today and NOT yet clocked out
        // ⚠️ Set TIMING_BYPASS_ATTENDANCE=true in .env to bypass attendance check (backup for fingerprint issues)
        $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);
        $clockedInToday = $bypassAttendance ? Employee::where('status', 'active')->where('department_id', $animatronicsDept->id)->pluck('id')->toArray() : AttendanceLog::whereDate('date', today())->whereNotNull('clock_in')->whereNull('clock_out')->pluck('employee_id')->toArray();

        // Get employees with running sessions for today
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get active animatronics employees (only clocked-in, exclude those with active sessions)
        $employees = Employee::where('status', 'active')->where('department_id', $animatronicsDept->id)->whereIn('id', $clockedInToday)->whereNotIn('id', $employeesWithActiveSessions)->with('department')->orderBy('name')->get();

        // Get Mascot + Animatronics department IDs (shared workload between these departments)
        $sharedDepts = Department::where(function ($q) {
            $q->where('name', 'LIKE', '%mascot%')->orWhere('name', 'LIKE', '%animatronic%')->orWhere('name', 'LIKE', '%animation%');
        })
            ->pluck('id')
            ->toArray();

        // Job Orders: filter by Mascot/Animatronics department (via pivot or direct) + status != Delivered
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
            ->orderBy('created_at', 'desc')
            ->get();

        // Get active timing sessions for animatronics (INDIVIDUAL, not grouped)
        $activeSessions = Timing::running()
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($query) use ($animatronicsDept) {
                $query->where('department_id', $animatronicsDept->id);
            })
            ->orderBy('start_time', 'desc')
            ->get();

        // Get department config
        $config = $this->deptTimingService->getDepartmentConfig($animatronicsDept->id);

        // Get positions in animatronics dept
        $positions = Employee::where('status', 'active')->where('department_id', $animatronicsDept->id)->whereNotNull('position')->distinct()->pluck('position')->sort();

        $departments = Department::orderBy('name')->get();

        return view('timing.animatronics.index', compact('employees', 'jobOrders', 'activeSessions', 'config', 'positions', 'animatronicsDept', 'employeesWithActiveSessions', 'departments'));
    }

    /**
     * Start work session for animatronics employees
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'employees' => 'required|array|min:1',
            'employees.*' => 'exists:employees,id',
            'job_order_id' => 'required|exists:job_orders,id',
            'step' => 'required|string|max:255',
            'parts' => 'nullable|string|max:255',
            'tracking_mode' => 'required|in:timer,progress',
            'department_specific_data' => 'nullable|array',
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

                // Employee must have clocked in today and NOT yet clocked out
                // ⚠️ Set TIMING_BYPASS_ATTENDANCE=true in .env to bypass (backup for fingerprint issues)
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

                // Get employee department
                $employee = Employee::with('department')->find($employeeId);
                $deptConfig = $this->deptTimingService->getDepartmentConfig($employee->department_id);

                // Fingerprint validation: enrolled employees must have tapped IN today
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

                // Prepare department-specific data
                $deptSpecificData = $validated['department_specific_data'] ?? [];
                $deptSpecificData['tracking_mode'] = $validated['tracking_mode'];
                $deptSpecificData['measurement_type'] = $validated['tracking_mode'] === 'progress' ? 'progress' : 'quantity';

                // For progress mode, get previous progress (SHARED per job order, bukan per employee)
                if ($validated['tracking_mode'] === 'progress') {
                    // Get latest timing for this job order (regardless of employee)
                    $lastTiming = Timing::where('job_order_id', $validated['job_order_id'])
                        ->whereNotNull('department_specific_data')
                        ->whereNotNull('end_time') // Only completed sessions
                        ->latest('tanggal')
                        ->latest('end_time')
                        ->first();

                    $previousProgress = 0;
                    if ($lastTiming && isset($lastTiming->department_specific_data['current_progress'])) {
                        $previousProgress = $lastTiming->department_specific_data['current_progress'];
                    }

                    $deptSpecificData['previous_progress'] = $previousProgress;
                    $deptSpecificData['current_progress'] = $previousProgress; // Will be updated on stop
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
                    'output_qty' => 0,
                    'status' => 'on progress',
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
                    'step' => $timing->step,
                    'parts' => $timing->parts,
                    'start_time' => $timing->start_time,
                    'duration' => '00:00:00',
                    'tracking_mode' => $validated['tracking_mode'],
                    'previous_progress' => $deptSpecificData['previous_progress'] ?? 0,
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
            'measurement_type' => 'required|string|in:qty,pcs,unit,piece,item,set,meter,cm,kg,gram,percentage', // Add percentage
            'stage' => 'nullable|integer|min:1|max:10', // Stage for progress mode (1-10)
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:5120', // Photo optional (not required)
            'department_specific_data' => 'nullable|array',
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

            $deptSpecificData = $timing->department_specific_data ?? [];

            // Merge with new data
            if (isset($validated['department_specific_data'])) {
                $deptSpecificData = array_merge($deptSpecificData, $validated['department_specific_data']);
            }

            // For progress mode, calculate current progress using stage (ABSOLUTE, not additive)
            if (isset($deptSpecificData['tracking_mode']) && $deptSpecificData['tracking_mode'] === 'progress') {
                $previousProgress = $deptSpecificData['previous_progress'] ?? 0;

                // If stage is provided, use it to set ABSOLUTE progress (stage 1 = 10%, stage 2 = 20%, etc)
                if (isset($validated['stage'])) {
                    $stage = $validated['stage'];
                    $currentProgress = $stage * 10; // Absolute positioning: stage represents current position
                    $progressAdded = $currentProgress - $previousProgress; // Calculate increment for display
                    $deptSpecificData['stage'] = $stage;
                    $deptSpecificData['progress_added'] = $progressAdded;
                    $deptSpecificData['current_progress'] = min(100, $currentProgress); // Absolute value, not cumulative
                } else {
                    // Fallback: use output_qty directly as absolute percentage
                    $currentProgress = $validated['output_qty'];
                    $progressAdded = $currentProgress - $previousProgress;
                    $deptSpecificData['progress_added'] = $progressAdded;
                    $deptSpecificData['current_progress'] = min(100, $currentProgress);
                }
            }

            // Calculate net duration in minutes (break time excluded)
            $today = now()->format('Y-m-d');
            $dur = $timing->start_time ? $this->computeTimingDuration($timing, $today, $timing->start_time, $endTime) : ['net' => 0, 'break' => 0];
            $durationMinutes = $dur['net'];

            // Determine measurement type (dari form untuk timer mode, atau percentage untuk progress mode)
            $trackingMode = $deptSpecificData['tracking_mode'] ?? 'timer';
            $measurementType = $trackingMode === 'progress' ? 'percentage' : $validated['measurement_type'];

            // Handle photo upload (OPTIONAL)
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = 'timing_' . $timing->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $photoPath = $photo->storeAs('timings/photos', $photoName, 'public');
            }

            $timing->update([
                'end_time' => $endTime,
                'output_qty' => $validated['output_qty'], // Keep for backward compatibility
                'measurement_type' => $measurementType,
                'measurement_value' => $validated['output_qty'],
                'duration_minutes' => $durationMinutes,
                'duration_hours' => round($durationMinutes / 60, 2),
                'break_deducted_minutes' => $dur['break'],
                'status' => 'complete',
                'approval_status' => 'pending',
                'department_specific_data' => $deptSpecificData,
                'photo' => $photoPath,
            ]);

            DB::commit();

            $trackingMode = $deptSpecificData['tracking_mode'] ?? 'timer';
            $modeLabel = $trackingMode === 'progress' ? 'progress' : 'output';

            return response()->json([
                'success' => true,
                'message' => 'Work session completed successfully with ' . $modeLabel . ': ' . $validated['output_qty'],
                'end_time' => $endTime,
                'timing_id' => $timing->id,
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
     * Bulk stop multiple animatronics timing sessions (from monitor)
     */
    public function bulkStop(Request $request)
    {
        $validated = $request->validate([
            'timing_ids' => 'required|array|min:1',
            'timing_ids.*' => 'required|exists:timings,id',
        ]);

        $endTime = now()->format('H:i:s');
        $stopped = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['timing_ids'] as $timingId) {
                $timing = Timing::where('id', $timingId)
                    ->whereIn('status', ['on progress', 'frozen'])
                    ->whereNull('end_time')
                    ->first();

                if (!$timing) {
                    $skipped++;
                    continue;
                }

                $today2 = now()->format('Y-m-d');
                $dur2 = $timing->start_time ? $this->computeTimingDuration($timing, $today2, $timing->start_time, $endTime) : ['net' => 0, 'break' => 0];

                $timing->update([
                    'end_time' => $endTime,
                    'measurement_type' => 'pcs',
                    'measurement_value' => 1,
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
     */
    public function getActiveSessions(\App\Services\Timing\TimingBreakService $breakService)
    {
        $breakService->run();

        // Get animatronics department
        $animatronicsDept = Department::where('name', 'LIKE', '%animatronic%')->orWhere('name', 'LIKE', '%animation%')->first();

        if (!$animatronicsDept) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Animatronics department not found.',
                ],
                404,
            );
        }

        $activeSessions = Timing::whereIn('status', ['on progress', 'frozen'])
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($query) use ($animatronicsDept) {
                $query->where('department_id', $animatronicsDept->id);
            })
            ->orderBy('start_time', 'desc')
            ->get();

        $sessions = $activeSessions->map(function ($timing) {
            $departmentData = $timing->department_specific_data ?? [];
            $trackingMode = $departmentData['tracking_mode'] ?? 'timer';
            $previousProgress = $departmentData['previous_progress'] ?? 0;
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
                'frozen_at' => $departmentData['frozen_at'] ?? null,
                'output_qty' => $timing->output_qty,
                'duration' => $isFrozen ? $departmentData['frozen_duration'] ?? '00:00:00' : $this->calculateDuration($timing->start_time),
                'is_frozen' => $isFrozen,
                'department_data' => [
                    'tracking_mode' => $trackingMode,
                    'previous_progress' => $previousProgress,
                ],
                'tracking_mode' => $trackingMode,
                'previous_progress' => $previousProgress,
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

    /**
     * Pause an active session (save as complete with status 'paused')
     * so the user can start a new job order without losing the current session.
     */
    public function pause(Request $request)
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
                        'message' => 'No active work session found to pause.',
                    ],
                    422,
                );
            }

            $pauseTime = now()->format('H:i:s');
            $today = now()->format('Y-m-d');

            // Calculate net duration so far (break time excluded)
            $durP = $timing->start_time ? $this->computeTimingDuration($timing, $today, $timing->start_time, $pauseTime) : ['net' => 0, 'break' => 0];
            $durationMinutes = $durP['net'];

            $deptSpecificData = $timing->department_specific_data ?? [];
            $deptSpecificData['paused_at'] = $pauseTime;

            // Map tracking_mode to valid ENUM values for measurement_type
            $trackingMode = $deptSpecificData['tracking_mode'] ?? 'timer';
            $measurementType = $trackingMode === 'progress' ? 'percentage' : 'pcs';

            $timing->update([
                'end_time' => $pauseTime,
                'duration_minutes' => $durationMinutes,
                'duration_hours' => round($durationMinutes / 60, 2),
                'break_deducted_minutes' => $durP['break'],
                'measurement_value' => 0,
                'measurement_type' => $measurementType,
                'status' => 'paused',
                'approval_status' => 'pending',
                'department_specific_data' => $deptSpecificData,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session paused. You can now start a new job order.',
                'timing_id' => $timing->id,
                'paused_at' => $pauseTime,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to pause session: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Freeze an active session (timer stops, card stays in monitor, NOT sent to approval)
     */
    public function freeze(Request $request)
    {
        $validated = $request->validate([
            'timing_id' => 'required|exists:timings,id',
        ]);

        try {
            DB::beginTransaction();

            $timing = Timing::where('id', $validated['timing_id'])->where('status', 'on progress')->whereNull('end_time')->first();

            if (!$timing) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No active session found to freeze.'], 422);
            }

            $frozenAt = now()->format('H:i:s');
            $today = now()->format('Y-m-d');
            $deptSpecificData = $timing->department_specific_data ?? [];

            // Calculate elapsed duration at freeze time
            $frozenDuration = '00:00:00';
            if ($timing->start_time) {
                $start = \Carbon\Carbon::parse($today . ' ' . $timing->start_time);
                $end = \Carbon\Carbon::parse($today . ' ' . $frozenAt);
                $diff = $start->diff($end);
                $frozenDuration = sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
            }

            $deptSpecificData['frozen_at'] = $frozenAt;
            $deptSpecificData['frozen_duration'] = $frozenDuration;

            // Append pause event to log
            $pauseLog = $timing->pause_log ?? [];
            $pauseLog[] = ['type' => 'manual', 'paused_at' => $frozenAt, 'resumed_at' => null, 'duration_minutes' => null];

            $timing->update([
                'status' => 'frozen',
                'paused_at' => now(),
                'department_specific_data' => $deptSpecificData,
                'pause_log' => $pauseLog,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session frozen. Timer is paused.',
                'timing_id' => $timing->id,
                'frozen_duration' => $frozenDuration,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to freeze: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unfreeze a frozen session (timer resumes, start_time is adjusted)
     */
    public function unfreeze(Request $request)
    {
        $validated = $request->validate([
            'timing_id' => 'required|exists:timings,id',
        ]);

        try {
            DB::beginTransaction();

            $timing = Timing::where('id', $validated['timing_id'])->where('status', 'frozen')->first();

            if (!$timing) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No frozen session found.'], 422);
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

            $deptSpecificData = $timing->department_specific_data ?? [];
            $frozenDuration = $deptSpecificData['frozen_duration'] ?? '00:00:00';

            // Adjust start_time so elapsed = frozenDuration when timer resumes
            [$h, $m, $s] = array_map('intval', explode(':', $frozenDuration));
            $frozenSeconds = $h * 3600 + $m * 60 + $s;
            $newStartTime = now()->subSeconds($frozenSeconds)->format('H:i:s');
            $pausedMins = $timing->paused_at ? (int) $timing->paused_at->diffInMinutes(now()) : 0;
            $resumedAt = now()->format('H:i:s');

            // Clean freeze fields
            unset($deptSpecificData['frozen_at'], $deptSpecificData['frozen_duration'], $deptSpecificData['auto_break_paused']);

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
                'department_specific_data' => $deptSpecificData,
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
                'timing_id' => $timing->id,
                'new_start_time' => $newStartTime,
                'auto_froze' => $autoFrozeName,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to unfreeze: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Quick-create a Job Order from the timing page.
     * Creates an InternalProject first, then a JobOrder linked to it.
     */
    public function quickStoreJobOrder(Request $request)
    {
        $validated = $request->validate([
            'jo_name' => 'required|string|max:255',
            'ip_type' => 'required|string|in:Office,Machine,Testing,Facilities,Store',
            'ip_description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
        ]);

        // ip_job falls back to jo_name if not provided
        $ipJob = $validated['jo_name'];

        try {
            DB::beginTransaction();

            $dept = \App\Models\Admin\Department::find($validated['department_id']);

            // 1. Create the InternalProject (same pattern as InternalProjectController::quickStore)
            $internalProject = \App\Models\InternalProject::create([
                'uid' => strtoupper(substr(md5(uniqid()), 0, 8)),
                'project' => $validated['ip_type'],
                'job' => $ipJob,
                'description' => $validated['ip_description'] ?? null,
                'department_id' => $dept?->id,
                'department' => $dept?->name ?? '',
                'pic' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            // 2. Create the JobOrder linked to the InternalProject
            $defaultProject = \App\Models\Production\Project::orderBy('id')->first();

            $jobOrder = \App\Models\Production\JobOrder::create([
                'name' => $validated['jo_name'],
                'project_id' => $defaultProject?->id,
                'department_id' => $dept?->id,
                'description' => 'IP: ' . $internalProject->id . ' - ' . $ipJob,
                'source_by' => auth()->id(),
            ]);

            $jobOrder->load('project');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Internal Project & Job Order created: ' . $jobOrder->name,
                'job_order' => [
                    'id' => $jobOrder->id,
                    'name' => $jobOrder->name,
                    'project_name' => $validated['jo_name'] . ' [' . $validated['ip_type'] . ']',
                    'project_id' => $jobOrder->project_id,
                    'ip_id' => $internalProject->id,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get available employees for the left panel (AJAX — avoids full page reload).
     */
    public function getAvailableEmployees()
    {
        $animatronicsDept = Department::where('name', 'LIKE', '%animatronic%')->orWhere('name', 'LIKE', '%animation%')->first();

        $bypassAttendance = (bool) env('TIMING_BYPASS_ATTENDANCE', false);
        $clockedInToday = $bypassAttendance ? Employee::where('status', 'active')->where('department_id', $animatronicsDept->id)->pluck('id')->toArray() : AttendanceLog::whereDate('date', today())->whereNotNull('clock_in')->whereNull('clock_out')->pluck('employee_id')->toArray();

        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        $query = Employee::where('status', 'active')
            ->whereIn('id', $clockedInToday)
            ->whereNotIn('id', $employeesWithActiveSessions)
            ->with(['skillsets'])
            ->orderBy('name');

        if ($animatronicsDept) {
            $query->where('department_id', $animatronicsDept->id);
        }

        $employees = $query->get();

        $frozenSessions = Timing::where('status', 'frozen')->today()->withRelations()->get();
        if ($animatronicsDept) {
            $frozenSessions = $frozenSessions->filter(fn($t) => $t->employee?->department_id === $animatronicsDept->id);
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
