<?php

namespace App\Http\Controllers\Timing\Mascot;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Hr\Employee;
use App\Models\Hr\Skillset;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MascotTimingController extends Controller
{
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

        // Get employees with running sessions for today
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get active mascot employees (exclude those with active sessions)
        $employees = Employee::where('status', 'active')
            ->where('department_id', $mascotDept->id)
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

        $units = Unit::orderBy('name')->get();

        return view('timing.mascot.index', compact('employees', 'employeesBySkillset', 'jobOrders', 'activeSessions', 'mascotDept', 'positions', 'employeesWithActiveSessions', 'units'));
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
            'task' => 'required|string|max:255', // Task description instead of 'step'
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

                $employee = Employee::find($employeeId);

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
                ];

                $timing = Timing::create([
                    'tanggal' => $today,
                    'job_order_id' => $validated['job_order_id'],
                    'project_id' => $jobOrder->project_id,
                    'step' => $validated['task'], // Store task in step field
                    'parts' => 'N/A', // Not used in mascot timing
                    'employee_id' => $employeeId,
                    'start_time' => $startTime,
                    'end_time' => null,
                    'measurement_type' => 'percentage', // Stage-based progress (use percentage from enum)
                    'measurement_value' => 0, // Will be set on stop
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
                    'task' => $timing->step,
                    'start_time' => $timing->start_time,
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
                'measurement_type' => $validated['measurement_type'],
                'measurement_value' => $validated['output_qty'],
                'duration_minutes' => $durationMinutes,
                'duration_hours' => round($durationMinutes / 60, 2),
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
                'duration_minutes' => $durationMinutes,
                'duration_hours' => round($durationMinutes / 60, 2),
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

                $durationMinutes = 0;
                if ($timing->start_time) {
                    try {
                        $today = now()->format('Y-m-d');
                        $start = \Carbon\Carbon::parse($today . ' ' . $timing->start_time);
                        $end = \Carbon\Carbon::parse($today . ' ' . $endTime);
                        $durationMinutes = $start->diffInMinutes($end);
                    } catch (\Exception $e) {
                    }
                }

                $timing->update([
                    'end_time' => $endTime,
                    'measurement_type' => $validated['measurement_type'],
                    'measurement_value' => $validated['output_qty'],
                    'duration_minutes' => $durationMinutes,
                    'duration_hours' => round($durationMinutes / 60, 2),
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
     * Get active sessions via AJAX (individual sessions)
     */
    public function getActiveSessions()
    {
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

        $activeSessions = Timing::running()
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($query) use ($mascotDept) {
                $query->where('department_id', $mascotDept->id);
            })
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($timing) {
                $durationSeconds = 0;
                if ($timing->start_time) {
                    $start = Carbon::parse($timing->tanggal . ' ' . $timing->start_time);
                    $now = now();
                    $durationSeconds = $start->diffInSeconds($now);
                }

                $previousStage = $timing->department_specific_data['previous_stage'] ?? 0;
                $previousProgress = $timing->department_specific_data['previous_progress'] ?? 0;

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
                    'duration_seconds' => $durationSeconds,
                    'previous_stage' => $previousStage,
                    'previous_progress' => $previousProgress,
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

        // Get latest stage for this job order
        $lastTiming = Timing::where('job_order_id', $jobOrderId)->whereNotNull('department_specific_data')->whereNotNull('end_time')->latest('tanggal')->latest('end_time')->first();

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
}
