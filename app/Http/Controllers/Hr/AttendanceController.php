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

        // Handle AJAX request untuk skill gap recalculation
        if ($request->input('ajax_skill_gap')) {
            $employees = Employee::with(['department', 'skillsets'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            $attendances = Attendance::whereDate('date', $date)->get()->keyBy('employee_id');

            $employees = $employees->map(function ($employee) use ($attendances) {
                $attendance = $attendances->get($employee->id);
                $employee->attendance = $attendance;
                $employee->attendance_status = $attendance ? $attendance->status : 'present';
                return $employee;
            });

            $skillGapAnalysis = $this->calculateSkillGap($date, $employees);

            return response()->json([
                'skillGapAnalysis' => $skillGapAnalysis,
            ]);
        }

        // Handle AJAX request untuk modal content
        if ($request->input('ajax_skill_gap_modal')) {
            $employees = Employee::with(['department', 'skillsets'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $attendances = Attendance::whereDate('date', $date)->get()->keyBy('employee_id');

            $employees = $employees->map(function ($employee) use ($attendances) {
                $attendance = $attendances->get($employee->id);
                $employee->attendance = $attendance;
                $employee->attendance_status = $attendance ? $attendance->status : 'present';
                return $employee;
            });

            $skillGapAnalysis = $this->calculateSkillGap($date, $employees);

            return view('hr.attendance.skill-gap-modal', compact('skillGapAnalysis'));
        }

        // Set default Present jika belum ada data untuk tanggal ini
        $this->autoInitializeAttendance($date);

        // Get all departments for filter
        $departments = Department::all();

        // Get unique positions from employees table
        $positions = Employee::select('position')->distinct()->whereNotNull('position')->orderBy('position')->pluck('position');

        // Query employees with filters
        $employees = Employee::with(['department', 'skillsets'])
            ->where('status', 'active')
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
        // Filter hanya active employees
        $employees = $employees->filter(function ($employee) {
            return $employee->status === 'active';
        });

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
                        'name' => $skillName,
                        'category' => $skillset->category,
                        'employees' => [],
                        'proficiency_levels' => [],
                        'count' => 0,
                    ];
                }

                $missingSkills[$skillName]['employees'][] = [
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
        // Validate employee is active
        $employee = Employee::findOrFail($request->employee_id);

        if ($employee->status !== 'active') {
            return response()->json(
                [
                    'success' => false,
                    'message' => "Cannot record attendance for {$employee->name}. Employee status is {$employee->status}.",
                ],
                422,
            );
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) {
                    $inputDate = Carbon::parse($value)->startOfDay();
                    $today = now()->startOfDay();

                    if ($inputDate->isAfter($today)) {
                        $fail('Attendance date cannot be in the future.');
                    }
                },
            ],
            'status' => 'required|in:present,absent,late',
            'notes' => 'nullable|string|max:500',
            'late_time' => 'required_if:status,late|nullable|date_format:H:i',
        ]);

        try {
            $data = [
                'status' => $request->status,
                'recorded_time' => now()->format('H:i:s'),
                'recorded_by' => auth()->id(),
                'notes' => $request->notes,
            ];

            if ($request->status === 'late' && $request->filled('late_time')) {
                try {
                    $data['late_time'] = Carbon::createFromFormat('H:i', $request->late_time)->format('H:i:s');
                } catch (\Exception $e) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'Invalid late time format',
                        ],
                        422,
                    );
                }
            } else {
                $data['late_time'] = null;
            }

            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'date' => $request->date,
                ],
                $data,
            );

            // Only load active employees for skill gap
            $employees = Employee::with(['department', 'skillsets'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $attendances = Attendance::whereDate('date', $request->date)->get()->keyBy('employee_id');

            $employees = $employees->map(function ($emp) use ($attendances) {
                $att = $attendances->get($emp->id);
                $emp->attendance = $att;
                $emp->attendance_status = $att ? $att->status : 'present';
                return $emp;
            });

            $skillGapAnalysis = $this->calculateSkillGap($request->date, $employees);

            return response()->json([
                'success' => true,
                'message' => "Attendance for {$employee->name} updated successfully",
                'data' => [
                    'status' => $attendance->status,
                    'recorded_time' => Carbon::parse($attendance->recorded_time)->format('h:i A'),
                    'late_time' => $attendance->late_time ? Carbon::parse($attendance->late_time)->format('H:i') : null,
                ],
                'skillGapAnalysis' => $skillGapAnalysis,
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
            'bulk_late_time' => 'required_if:status,late|nullable|date_format:H:i',
        ]);

        // ✨ PERBAIKAN: Check if all employees are active
        $inactiveEmployees = Employee::whereIn('id', $request->employee_ids)->where('status', '!=', 'active')->pluck('name')->toArray();

        if (!empty($inactiveEmployees)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Cannot update attendance for inactive/terminated employees: ' . implode(', ', $inactiveEmployees),
                ],
                422,
            );
        }

        try {
            DB::beginTransaction();

            $recordedTime = now()->format('H:i:s');
            $recordedBy = auth()->id();

            // Parse late_time jika status adalah late
            $lateTime = null;
            if ($request->status === 'late' && $request->filled('bulk_late_time')) {
                try {
                    $lateTime = Carbon::createFromFormat('H:i', $request->bulk_late_time)->format('H:i:s');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'Invalid late time format',
                        ],
                        422,
                    );
                }
            }

            foreach ($request->employee_ids as $employeeId) {
                $data = [
                    'status' => $request->status,
                    'recorded_time' => $recordedTime,
                    'recorded_by' => $recordedBy,
                ];

                if ($request->status === 'late') {
                    $data['late_time'] = $lateTime;
                } else {
                    $data['late_time'] = null;
                }

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'date' => $request->date,
                    ],
                    $data,
                );
            }

            DB::commit();

            $count = count($request->employee_ids);
            return response()->json([
                'success' => true,
                'message' => "{$count} employees marked as {$request->status}" . ($request->status === 'late' && $lateTime ? " at {$request->bulk_late_time}" : ''),
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
     * Bulk update attendances dengan individual late times
     */
    public function bulkUpdateIndividual(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:late', // Hanya untuk late status
            'employees_with_times' => 'required|array|min:1',
            'employees_with_times.*.employee_id' => 'required|exists:employees,id',
            'employees_with_times.*.late_time' => 'required|date_format:H:i',
        ]);

        try {
            DB::beginTransaction();

            $recordedTime = now()->format('H:i:s');
            $recordedBy = auth()->id();
            $successCount = 0;
            $errors = [];

            foreach ($request->employees_with_times as $item) {
                try {
                    $employeeId = $item['employee_id'];

                    // Parse late_time dengan format H:i
                    $lateTime = Carbon::createFromFormat('H:i', $item['late_time'])->format('H:i:s');

                    Attendance::updateOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'date' => $request->date,
                        ],
                        [
                            'status' => 'late',
                            'late_time' => $lateTime,
                            'recorded_time' => $recordedTime,
                            'recorded_by' => $recordedBy,
                        ],
                    );

                    $successCount++;
                } catch (\Exception $e) {
                    $employee = Employee::find($item['employee_id']);
                    $errors[] = ($employee->name ?? 'Employee ' . $item['employee_id']) . ': ' . $e->getMessage();
                }
            }

            DB::commit();

            if ($successCount > 0) {
                $message = "{$successCount} employee(s) marked as late with individual times";

                if (!empty($errors)) {
                    \Log::warning('Bulk late update partial errors', $errors);
                    $message .= ' (' . count($errors) . ' failed)';
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'success_count' => $successCount,
                    'error_count' => count($errors),
                ]);
            } else {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Failed to update any attendance records',
                    ],
                    422,
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk late update error: ' . $e->getMessage());

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

    // Attendance List View
    public function list(Request $request)
    {
        // If AJAX request, return DataTables data
        if ($request->ajax()) {
            return $this->getDataTablesDataList($request);
        }

        $departments = Department::all();
        $positions = Employee::select('position')->distinct()->whereNotNull('position')->orderBy('position')->pluck('position');

        // Calculate statistics for cards
        $stats = [
            'total_records' => Attendance::count(),
            'today_present' => Attendance::whereDate('date', now())->where('status', 'present')->count(),
            'today_absent' => Attendance::whereDate('date', now())->where('status', 'absent')->count(),
            'today_late' => Attendance::whereDate('date', now())->where('status', 'late')->count(),
        ];

        return view('hr.attendance.list', compact('departments', 'positions', 'stats'));
    }

    /**
     * Server-side processing for attendance list
     */
    private function getDataTablesDataList(Request $request)
    {
        $query = Attendance::with(['employee.department', 'recordedBy'])->latest('date');

        // Apply filters
        if ($request->filled('department_filter')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_filter);
            });
        }

        if ($request->filled('position_filter')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('position', $request->position_filter);
            });
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        if ($request->filled('date_from_filter')) {
            $query->whereDate('date', '>=', $request->date_from_filter);
        }

        if ($request->filled('date_to_filter')) {
            $query->whereDate('date', '<=', $request->date_to_filter);
        }

        // Custom search
        if ($request->filled('custom_search')) {
            $searchValue = $request->input('custom_search');
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('employee', function ($sq) use ($searchValue) {
                    $sq->where('name', 'like', "%{$searchValue}%")->orWhere('employee_no', 'like', "%{$searchValue}%");
                })->orWhere('notes', 'like', "%{$searchValue}%");
            });
        }

        // Sorting
        $columns = ['id', 'date', 'employee_id', 'status', 'recorded_time'];
        if ($request->filled('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'desc');

            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            }
        } else {
            $query->orderBy('date', 'desc')->orderBy('created_at', 'desc');
        }

        // Get total and filtered counts
        $totalRecords = Attendance::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        $attendances = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        foreach ($attendances as $index => $attendance) {
            $statusBadge = $this->getStatusBadge($attendance->status);

            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'date' => '<strong>' . $attendance->date->format('d M Y') . '</strong><br><small class="text-muted">' . $attendance->date->format('l') . '</small>',
                'employee' => $attendance->employee ? '<div><strong>' . $attendance->employee->name . '</strong><br><small class="text-muted">' . ($attendance->employee->employee_no ?? '-') . '</small></div>' : '<span class="text-warning">Unknown Employee</span>',
                'department' => $attendance->employee ? $attendance->employee->department->name ?? 'N/A' : 'N/A',
                'position' => $attendance->employee ? $attendance->employee->position ?? 'N/A' : 'N/A',
                'status' => $statusBadge,
                'arrival_time' => $attendance->status == 'late' && $attendance->late_time ? '<span class="text-warning fw-bold"><i class="bi bi-clock"></i> ' . \Carbon\Carbon::parse($attendance->late_time)->format('H:i') . '</span>' : '<span class="text-muted">-</span>',
                'recorded_time' => '<i class="bi bi-clock-history"></i> ' . \Carbon\Carbon::parse($attendance->recorded_time)->format('h:i A'),
                'recorded_by' => '<small>' . ($attendance->recordedBy->name ?? 'System') . '</small>',
                'notes' => $attendance->notes ? '<span class="text-truncate d-inline-block" style="max-width: 150px;" data-bs-toggle="tooltip" title="' . $attendance->notes . '">' . $attendance->notes . '</span>' : '<span class="text-muted">-</span>',
                'actions' => $this->getAttendanceActionButtons($attendance),
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function getStatusBadge($status)
    {
        $badges = [
            'present' => '<span class="badge bg-success rounded-pill px-3 py-2 shadow-sm"><i class="bi bi-check-circle"></i> Present</span>',
            'absent' => '<span class="badge bg-danger rounded-pill px-3 py-2 shadow-sm"><i class="bi bi-x-circle"></i> Absent</span>',
            'late' => '<span class="badge bg-warning rounded-pill px-3 py-2 shadow-sm"><i class="bi bi-clock"></i> Late</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    private function getAttendanceActionButtons($attendance)
    {
        return '<button type="button" class="btn btn-sm btn-danger btn-delete rounded-pill shadow-sm"
        data-id="' .
            $attendance->id .
            '"
        data-name="' .
            ($attendance->employee->name ?? 'Unknown Employee') .
            '"
        data-date="' .
            $attendance->date->format('Y-m-d') .
            '">
        <i class="bi bi-trash"></i>
    </button>';
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
