<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use App\Models\Hr\Skillset;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $department_id = $request->input('department_id');
        $position = $request->input('position');
        $status = $request->input('status');
        $search = $request->input('search');

        // Set default Present jika belum ada data untuk tanggal ini
        $this->autoInitializeAttendance($date);

        // Get all departments for filter
        $departments = Department::all();

        // Get unique positions from employees table
        $positions = Employee::select('position')->distinct()->whereNotNull('position')->orderBy('position')->pluck('position');

        // Query employees with filters
        $employees = Employee::with(['department', 'skillsets'])
            ->when($department_id, function ($query) use ($department_id) {
                return $query->where('department_id', $department_id);
            })
            ->when($position, function ($query) use ($position) {
                return $query->where('position', $position);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        // Get attendances for the date
        $attendances = Attendance::whereDate('date', $date)->get()->keyBy('employee_id');

        // Attach attendance status to employees
        $employees = $employees->map(function ($employee) use ($attendances) {
            $attendance = $attendances->get($employee->id);
            $employee->attendance = $attendance;
            $employee->attendance_status = $attendance ? $attendance->status : 'present';
            $employee->recorded_time = $attendance ? $attendance->recorded_time : null;
            return $employee;
        });

        // Filter by status if specified
        if ($status) {
            $employees = $employees->filter(function ($employee) use ($status) {
                return $employee->attendance_status === $status;
            });
        }

        // Calculate summary
        $summary = [
            'total' => $employees->count(),
            'present' => $employees->where('attendance_status', 'present')->count(),
            'absent' => $employees->where('attendance_status', 'absent')->count(),
            'late' => $employees->where('attendance_status', 'late')->count(),
        ];

        // Calculate skill gap analysis
        $skillGapAnalysis = $this->calculateSkillGap($date, $employees);

        return view('hr.attendance.index', compact('employees', 'departments', 'positions', 'date', 'department_id', 'position', 'status', 'search', 'summary', 'skillGapAnalysis'));
    }

    /**
     * Calculate skill gap for absent/late employees
     */
    private function calculateSkillGap($date, $employees)
    {
        // Get absent and late employees
        $absentOrLate = $employees->filter(function ($employee) {
            return in_array($employee->attendance_status, ['absent', 'late']);
        });

        if ($absentOrLate->isEmpty()) {
            return [
                'total_affected_employees' => 0,
                'missing_skills' => [],
                'critical_skills' => [],
            ];
        }

        // Collect all missing skills
        $missingSkills = [];
        $skillCounts = [];

        foreach ($absentOrLate as $employee) {
            foreach ($employee->skillsets as $skillset) {
                $skillName = $skillset->name;
                $proficiency = $skillset->pivot->proficiency_level;

                if (!isset($missingSkills[$skillName])) {
                    $missingSkills[$skillName] = [
                        'skillset_id' => $skillset->id,
                        'name' => $skillName,
                        'category' => $skillset->category,
                        'employees' => [],
                        'proficiency_levels' => [],
                        'count' => 0,
                    ];
                }

                $missingSkills[$skillName]['employees'][] = [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'status' => $employee->attendance_status,
                    'proficiency' => $proficiency,
                ];

                $missingSkills[$skillName]['proficiency_levels'][] = $proficiency;
                $missingSkills[$skillName]['count']++;

                if (!isset($skillCounts[$skillName])) {
                    $skillCounts[$skillName] = 0;
                }
                $skillCounts[$skillName]++;
            }
        }

        // Identify critical skills (skills that are missing from 2+ employees)
        $criticalSkills = array_filter($missingSkills, function ($skill) {
            return $skill['count'] >= 2;
        });

        // Sort by count (most impacted first)
        uasort($missingSkills, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return [
            'total_affected_employees' => $absentOrLate->count(),
            'missing_skills' => $missingSkills,
            'critical_skills' => $criticalSkills,
            'has_critical_impact' => !empty($criticalSkills),
        ];
    }

    /**
     * Set all employees to Present if no attendance exists
     */
    private function autoInitializeAttendance($date)
    {
        // Check jika sudah ada attendance untuk tanggal ini
        $hasAttendance = Attendance::whereDate('date', $date)->exists();

        if (!$hasAttendance) {
            try {
                DB::beginTransaction();

                $employees = Employee::where('status', 'active')->get(); // Only active employees
                $recordedTime = now()->format('H:i:s');
                $recordedBy = auth()->id();

                $insertData = [];
                foreach ($employees as $employee) {
                    $insertData[] = [
                        'employee_id' => $employee->id,
                        'date' => $date,
                        'status' => 'present', // Default Present
                        'recorded_time' => $recordedTime,
                        'recorded_by' => $recordedBy,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($insertData)) {
                    Attendance::insert($insertData);
                }

                DB::commit();

                // Optional: Log atau notification
                \Log::info("Auto-initialized attendance for {$date}: " . count($insertData) . ' employees');
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Failed to auto-initialize attendance: ' . $e->getMessage());
            }
        }
    }

    /**
     * Store/Update attendance (AJAX)
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late',
            'notes' => 'nullable|string|max:500',
            'late_time' => 'nullable|date_format:H:i',
        ]);

        try {
            $data = [
                'status' => $request->status,
                'recorded_time' => now()->format('H:i:s'),
                'recorded_by' => auth()->id(),
                'notes' => $request->notes,
            ];

            // If status is late and has late_time, save it
            if ($request->status === 'late' && $request->late_time) {
                $data['late_time'] = $request->late_time;
            } else {
                // Clear late_time if status is not late
                $data['late_time'] = null;
            }

            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'date' => $request->date,
                ],
                $data,
            );

            $employee = Employee::find($request->employee_id);

            return response()->json([
                'success' => true,
                'message' => "Attendance for {$employee->name} updated successfully",
                'data' => [
                    'status' => $attendance->status,
                    'recorded_time' => Carbon::parse($attendance->recorded_time)->format('h:i A'),
                    'late_time' => $attendance->late_time ? Carbon::parse($attendance->late_time)->format('H:i') : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to update attendance: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Bulk update attendances
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'status' => 'required|in:present,absent,late',
        ]);

        try {
            DB::beginTransaction();

            $recordedTime = now()->format('H:i:s');
            $recordedBy = auth()->id();

            foreach ($request->employee_ids as $employeeId) {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'date' => $request->date,
                    ],
                    [
                        'status' => $request->status,
                        'recorded_time' => $recordedTime,
                        'recorded_by' => $recordedBy,
                    ],
                );
            }

            DB::commit();

            $count = count($request->employee_ids);
            return response()->json([
                'success' => true,
                'message' => "{$count} employees marked as {$request->status}",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to bulk update: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Initialize default attendances (set all to present)
     * Tetap dipertahankan untuk manual trigger jika diperlukan
     */
    public function initializeDefault(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $employees = Employee::where('status', 'active')->get();
            $recordedTime = now()->format('H:i:s');
            $recordedBy = auth()->id();

            $insertData = [];
            foreach ($employees as $employee) {
                // Check if attendance already exists
                $exists = Attendance::where('employee_id', $employee->id)->whereDate('date', $request->date)->exists();

                if (!$exists) {
                    $insertData[] = [
                        'employee_id' => $employee->id,
                        'date' => $request->date,
                        'status' => 'present',
                        'recorded_time' => $recordedTime,
                        'recorded_by' => $recordedBy,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($insertData)) {
                Attendance::insert($insertData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Default attendance initialized',
                'count' => count($insertData),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to initialize: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
    public function list(Request $request)
    {
        $department_id = $request->input('department_id');
        $position = $request->input('position');
        $status = $request->input('status');
        $search = $request->input('search');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $per_page = $request->input('per_page', 25);

        // Get all departments for filter
        $departments = Department::all();

        // Get unique positions
        $positions = Employee::select('position')->distinct()->whereNotNull('position')->orderBy('position')->pluck('position');

        // Query attendances with relationships
        $attendances = Attendance::with(['employee.department', 'recordedBy'])
            ->when($department_id, function ($query) use ($department_id) {
                return $query->whereHas('employee', function ($q) use ($department_id) {
                    $q->where('department_id', $department_id);
                });
            })
            ->when($position, function ($query) use ($position) {
                return $query->whereHas('employee', function ($q) use ($position) {
                    $q->where('position', $position);
                });
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%");
                });
            })
            ->when($date_from, function ($query) use ($date_from) {
                return $query->whereDate('date', '>=', $date_from);
            })
            ->when($date_to, function ($query) use ($date_to) {
                return $query->whereDate('date', '<=', $date_to);
            })
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($per_page);

        // Calculate statistics
        $stats = [
            'total_records' => Attendance::count(),
            'today_present' => Attendance::whereDate('date', now())->where('status', 'present')->count(),
            'today_absent' => Attendance::whereDate('date', now())->where('status', 'absent')->count(),
            'today_late' => Attendance::whereDate('date', now())->where('status', 'late')->count(),
        ];

        return view('hr.attendance.list', compact('attendances', 'departments', 'positions', 'department_id', 'position', 'status', 'search', 'date_from', 'date_to', 'per_page', 'stats'));
    }

    /**
     * Export attendance list to Excel
     */
    public function exportList(Request $request)
    {
        $department_id = $request->input('department_id');
        $position = $request->input('position');
        $status = $request->input('status');
        $search = $request->input('search');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');

        // Query sama seperti list()
        $attendances = Attendance::with(['employee.department', 'recordedBy'])
            ->when($department_id, function ($query) use ($department_id) {
                return $query->whereHas('employee', function ($q) use ($department_id) {
                    $q->where('department_id', $department_id);
                });
            })
            ->when($position, function ($query) use ($position) {
                return $query->whereHas('employee', function ($q) use ($position) {
                    $q->where('position', $position);
                });
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($date_from, function ($query) use ($date_from) {
                return $query->whereDate('date', '>=', $date_from);
            })
            ->when($date_to, function ($query) use ($date_to) {
                return $query->whereDate('date', '<=', $date_to);
            })
            ->orderBy('date', 'desc')
            ->get();

        // Create CSV
        $filename = 'attendance_list_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($attendances) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, ['Employee No', 'Employee Name', 'Department', 'Position', 'Date', 'Status', 'Recorded Time', 'Recorded By', 'Notes']);

            // Data
            foreach ($attendances as $attendance) {
                fputcsv($file, [$attendance->employee->employee_no ?? '-', $attendance->employee->name ?? '-', $attendance->employee->department->name ?? '-', $attendance->employee->position ?? '-', $attendance->date->format('Y-m-d'), ucfirst($attendance->status), Carbon::parse($attendance->recorded_time)->format('H:i:s'), $attendance->recordedBy->name ?? '-', $attendance->notes ?? '-']);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete attendance record
     */
    public function destroy($id)
    {
        try {
            $attendance = Attendance::findOrFail($id);
            $employeeName = $attendance->employee->name;
            $date = $attendance->date->format('Y-m-d');

            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => "Attendance record for {$employeeName} on {$date} deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to delete: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
