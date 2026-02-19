<?php

namespace App\Http\Controllers\Timing\Animatronics;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use App\Services\DepartmentTimingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AnimatronicsTimingController extends Controller
{
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

        // Get employees with running sessions for today
        $employeesWithActiveSessions = Timing::running()->today()->pluck('employee_id')->toArray();

        // Get active animatronics employees (exclude those with active sessions)
        $employees = Employee::where('status', 'active')->where('department_id', $animatronicsDept->id)->whereNotIn('id', $employeesWithActiveSessions)->with('department')->orderBy('name')->get();

        // Get ALL job orders (tidak filter by department, karena dept lain bisa dikerjakan animatronics)
        $jobOrders = JobOrder::with(['project', 'department'])
            ->whereNull('deleted_at')
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

        return view('timing.animatronics.index', compact('employees', 'jobOrders', 'activeSessions', 'config', 'positions', 'animatronicsDept', 'employeesWithActiveSessions'));
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

                // Get employee department
                $employee = Employee::with('department')->find($employeeId);
                $deptConfig = $this->deptTimingService->getDepartmentConfig($employee->department_id);

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

            // For progress mode, calculate current progress using stage
            if (isset($deptSpecificData['tracking_mode']) && $deptSpecificData['tracking_mode'] === 'progress') {
                $previousProgress = $deptSpecificData['previous_progress'] ?? 0;

                // If stage is provided, use it to calculate progress (each stage = 10%)
                if (isset($validated['stage'])) {
                    $stage = $validated['stage'];
                    $progressAdded = $stage * 10; // Each stage represents 10%
                    $deptSpecificData['stage'] = $stage;
                    $deptSpecificData['progress_added'] = $progressAdded;
                } else {
                    // Fallback: use output_qty directly as percentage
                    $progressAdded = $validated['output_qty'];
                    $deptSpecificData['progress_added'] = $progressAdded;
                }

                $deptSpecificData['current_progress'] = min(100, $previousProgress + $progressAdded);
            }

            // Calculate duration in hours
            $durationHours = 0;
            if ($timing->start_time && $endTime) {
                try {
                    $today = now()->format('Y-m-d');
                    $start = \Carbon\Carbon::parse($today . ' ' . $timing->start_time);
                    $end = \Carbon\Carbon::parse($today . ' ' . $endTime);
                    $durationHours = round($start->diffInMinutes($end) / 60, 2);
                } catch (\Exception $e) {
                    $durationHours = 0;
                }
            }

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
                'duration_hours' => $durationHours,
                'status' => 'complete',
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
     * Get active sessions via AJAX (individual sessions, not grouped)
     */
    public function getActiveSessions()
    {
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

        $activeSessions = Timing::running()
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
                'department_data' => [
                    'tracking_mode' => $trackingMode,
                    'previous_progress' => $previousProgress,
                ],
                // Also include at root level for backward compatibility
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
}
