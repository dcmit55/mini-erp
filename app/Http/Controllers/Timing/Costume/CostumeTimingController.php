<?php

namespace App\Http\Controllers\Timing\Costume;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
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
        // Get costume department
        $costumeDept = Department::where('name', 'LIKE', '%costume%')->orWhere('name', 'LIKE', '%sewing%')->first();

        // Get employees with running sessions for today
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get active costume employees (exclude those with active sessions)
        if ($costumeDept) {
            $employees = Employee::where('status', 'active')->where('department_id', $costumeDept->id)->whereNotIn('id', $employeesWithActiveSessions)->with('department')->orderBy('name')->get();

            // Get job orders related to costume
            $jobOrders = JobOrder::with(['project', 'department'])
                ->where('department_id', $costumeDept->id)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Fallback to all employees if no costume dept found
            $employees = Employee::where('status', 'active')->whereNotIn('id', $employeesWithActiveSessions)->with('department')->orderBy('name')->get();

            $jobOrders = JobOrder::with(['project', 'department'])
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Get unique departments from active employees
        $departments = Employee::where('status', 'active')->with('department')->get()->pluck('department')->unique('id')->filter()->sortBy('name');

        // Get unique positions from active employees
        $positions = Employee::where('status', 'active')->whereNotNull('position')->distinct()->pluck('position')->sort();

        // Get active timing sessions (individual cards, not grouped)
        $activeSessions = Timing::running()->today()->withRelations()->orderBy('start_time', 'desc')->get();

        return view('timing.costume.index', compact('employees', 'jobOrders', 'activeSessions', 'departments', 'positions', 'employeesWithActiveSessions'));
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
                    'output_qty' => 0,
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
            'output_qty' => 'required|integer|min:0',
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

            $timing->update([
                'end_time' => $endTime,
                'output_qty' => $validated['output_qty'],
                'status' => 'complete',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work session completed successfully with output: ' . $validated['output_qty'] . ' pieces',
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
     * Get active sessions via AJAX (individual sessions, not grouped)
     */
    public function getActiveSessions()
    {
        $activeSessions = Timing::running()->today()->withRelations()->orderBy('start_time', 'desc')->get();

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
                'output_qty' => $timing->output_qty,
                'duration' => $this->calculateDuration($timing->start_time),
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
