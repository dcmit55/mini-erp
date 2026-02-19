<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuickTimerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the quick timer index page
     */
    public function index()
    {
        // Get employees with running sessions for today (using scopes)
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get active employees (exclude those with active sessions)
        $employees = Employee::where('status', 'active')->whereNotIn('id', $employeesWithActiveSessions)->with('department')->orderBy('name')->get();

        // Get unique departments from active employees
        $departments = Employee::where('status', 'active')->with('department')->get()->pluck('department')->unique('id')->filter()->sortBy('name');

        // Get unique positions from active employees
        $positions = Employee::where('status', 'active')->whereNotNull('position')->distinct()->pluck('position')->sort();

        // Get job orders with project relationship
        $jobOrders = JobOrder::with(['project', 'department'])
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get active timing sessions (using scopes)
        $activeSessions = Timing::running()->today()->withRelations()->orderBy('start_time', 'desc')->get();

        return view('production.quick-timer.index', compact('employees', 'jobOrders', 'activeSessions', 'departments', 'positions', 'employeesWithActiveSessions'));
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

            $timingIds = [];
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

                $timingIds[] = $timing->id;
                $employee = Employee::find($employeeId);
                $employeeNames[] = $employee->name;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work started for ' . count($timingIds) . ' employee(s): ' . implode(', ', $employeeNames),
                'timing_ids' => $timingIds,
                'start_time' => $startTime,
                'job_order' => $jobOrder->id,
                'project_name' => $jobOrder->project->name ?? 'N/A',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
     * Stop work session
     */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'timing_ids' => 'required|array',
            'timing_ids.*' => 'exists:timings,id',
            'output_qty' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $endTime = now()->format('H:i:s');

            $timings = Timing::whereIn('id', $validated['timing_ids'])->where('status', 'on progress')->whereNull('end_time')->get();

            if ($timings->isEmpty()) {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No active work sessions found to stop.',
                    ],
                    422,
                );
            }

            foreach ($timings as $timing) {
                $timing->update([
                    'end_time' => $endTime,
                    'output_qty' => $validated['output_qty'],
                    'status' => 'complete',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($timings) . ' work session(s) completed successfully with output: ' . $validated['output_qty'],
                'end_time' => $endTime,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
     * Get active sessions via AJAX
     */
    public function getActiveSessions()
    {
        // Using scopes for cleaner code
        $activeSessions = Timing::running()->today()->withRelations()->orderBy('start_time', 'desc')->get();

        $sessions = $activeSessions->map(function ($timing) {
            return [
                'id' => $timing->id,
                'employee_name' => $timing->employee->name ?? 'N/A',
                'employee_photo' => $timing->employee->photo ?? null,
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

    /**
     * Get project info when job order is selected
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

        return response()->json([
            'success' => true,
            'job_order' => [
                'id' => $jobOrder->id,
                'name' => $jobOrder->name,
                'project_id' => $jobOrder->project_id,
                'project_name' => $jobOrder->project->name ?? 'N/A',
                'department_name' => $jobOrder->department->name ?? 'N/A',
                'description' => $jobOrder->description,
            ],
        ]);
    }
}
