<?php

namespace App\Http\Controllers\Timing\Costume;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Hr\Employee;
use App\Models\Hr\Skillset;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostumeTimingController extends Controller
{
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

        // Get employees with running sessions for today
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get active costume employees (exclude those with active sessions)
        if (!empty($costumeDeptIds)) {
            $employees = Employee::where('status', 'active')
                ->whereIn('department_id', $costumeDeptIds)
                ->whereNotIn('id', $employeesWithActiveSessions)
                ->with(['department', 'skillsets'])
                ->orderBy('name')
                ->get();

            $jobOrders = JobOrder::with(['project', 'department'])
                ->whereIn('department_id', $costumeDeptIds)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'not like', '%deliver%');
                })
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $employees = Employee::where('status', 'active')
                ->whereNotIn('id', $employeesWithActiveSessions)
                ->with(['department', 'skillsets'])
                ->orderBy('name')
                ->get();

            $jobOrders = JobOrder::with(['project', 'department'])
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'not like', '%deliver%');
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

        $units = Unit::orderBy('name')->get();

        return view('timing.costume.index', compact('employees', 'employeesBySkillset', 'jobOrders', 'activeSessions', 'departments', 'positions', 'employeesWithActiveSessions', 'units'));
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
            ->with(['employees' => fn($q) => $q
                ->whereIn('employees.id', $employeeIds)
                ->orderBy('name')
            ])
            ->orderBy('name')
            ->get();

        $groups = $skillsets->map(fn($skillset) => [
            'skillset_id' => $skillset->id,
            'label'       => $skillset->name,
            'employees'   => $skillset->employees->map(fn($emp) => [
                'employee' => $employeeMap->get($emp->id) ?? $emp,
            ]),
        ]);

        $assignedIds = $skillsets->flatMap(fn($s) => $s->employees->pluck('id'))->unique();
        $unassigned  = $employees->whereNotIn('id', $assignedIds);

        if ($unassigned->isNotEmpty()) {
            $groups->push([
                'skillset_id' => null,
                'label'       => 'Other',
                'employees'   => $unassigned->map(fn($emp) => ['employee' => $emp]),
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

            // Calculate duration in minutes (standardized storage)
            $durationMinutes = 0;
            if ($timing->start_time && $endTime) {
                try {
                    $today = now()->format('Y-m-d');
                    $start = \Carbon\Carbon::parse($today . ' ' . $timing->start_time);
                    $end = \Carbon\Carbon::parse($today . ' ' . $endTime);
                    $durationMinutes = $start->diffInMinutes($end);
                } catch (\Exception $e) {
                    $durationMinutes = 0;
                }
            }

            $timing->update([
                'end_time' => $endTime,
                'measurement_type' => $validated['measurement_type'],
                'measurement_value' => $validated['output_qty'],
                'duration_minutes' => $durationMinutes, // Standardized duration storage
                'duration_hours' => round($durationMinutes / 60, 2), // Derived for backward compatibility
                'status' => 'complete',
                'approval_status' => 'pending', // Default to pending approval
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work session completed successfully with output: ' . $validated['output_qty'] . ' ' . $validated['measurement_type'],
                'end_time' => $endTime,
                'timing_id' => $timing->id,
                'duration_minutes' => $durationMinutes,
                'duration_hours' => round($durationMinutes / 60, 2),
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
                    'measurement_type' => 'pcs',
                    'measurement_value' => 1,
                    'duration_minutes' => $durationMinutes,
                    'duration_hours' => round($durationMinutes / 60, 2),
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
    public function getActiveSessions()
    {
        // Get costume department
        $costumeDept = Department::where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%')->first();

        if (!$costumeDept) {
            return response()->json([
                'success' => true,
                'sessions' => [], // No costume dept = no sessions
            ]);
        }

        // FILTER by costume department ONLY
        $activeSessions = Timing::running()
            ->today()
            ->withRelations()
            ->whereHas('employee', function ($query) use ($costumeDept) {
                $query->where('department_id', $costumeDept->id);
            })
            ->orderBy('start_time', 'desc')
            ->get();

        $sessions = $activeSessions->map(function ($timing) {
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
                'measurement_type' => $timing->measurement_type ?? 'pcs',
                'measurement_value' => $timing->measurement_value ?? 0,
                'duration' => $this->calculateDuration($timing->start_time),
            ];
        });

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
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
