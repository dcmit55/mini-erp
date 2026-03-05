<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Production\Timing;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Exports\TimingExport;
use App\Exports\ImportTimingTemplate;
use App\Imports\TimingImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TimingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $timings = Timing::with(['project', 'employee.department', 'jobOrder'])
            ->orderByDesc('created_at')
            ->orderByDesc('start_time')
            ->get();

        $projects = Project::with('departments')->orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::orderBy('name')->get();
        $departments = Department::orderBy('name')->pluck('name', 'id');
        $employees = Employee::orderBy('name')->get();

        return view('production.timings.index', compact('timings', 'projects', 'jobOrders', 'departments', 'employees'));
    }

    public function ajaxSearch(Request $request)
    {
        $query = Timing::with(['project', 'employee.department', 'jobOrder']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('step', 'like', '%' . $request->search . '%')->orWhere('remarks', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('job_order_id')) {
            $query->where('job_order_id', $request->job_order_id);
        }
        if ($request->filled('department')) {
            $query->whereHas('project.departments', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Sort by newest first: created_at DESC, then start_time DESC for same-day entries
        $timings = $query->orderByDesc('created_at')->orderByDesc('start_time')->get();

        try {
            // Generate table rows HTML inline
            $html = '';

            if ($timings->isEmpty()) {
                $html = '<tr class="no-data-row">
                    <td colspan="16" class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="mt-2 text-muted">No timing data found</p>
                    </td>
                </tr>';
            } else {
                foreach ($timings as $timing) {
                    // Calculate duration in minutes
                    $minutes = 0;
                    if ($timing->duration_minutes && $timing->duration_minutes > 0) {
                        $minutes = $timing->duration_minutes;
                    } elseif ($timing->start_time && $timing->end_time) {
                        $start = \Carbon\Carbon::parse($timing->start_time);
                        $end = \Carbon\Carbon::parse($timing->end_time);
                        $minutes = $start->diffInMinutes($end);
                    }

                    $html .= '<tr>';
                    $html .= '<td class="date-col">' . ($timing->tanggal ? \Carbon\Carbon::parse($timing->tanggal)->format('d M Y') : '-') . '</td>';
                    $html .= '<td>' . ($timing->project ? $timing->project->name : '-') . '</td>';
                    $html .= '<td>' . ($timing->jobOrder ? $timing->jobOrder->name : '-') . '</td>';
                    $html .= '<td>' . ($timing->employee && $timing->employee->department ? $timing->employee->department->name : '-') . '</td>';
                    $html .= '<td>' . ($timing->step ?? '-') . '</td>';
                    $html .= '<td>' . ($timing->parts ?? '-') . '</td>';
                    $html .= '<td>' . ($timing->employee ? $timing->employee->name : '-') . '</td>';
                    $html .= '<td>' . ($timing->start_time ? \Carbon\Carbon::parse($timing->start_time)->format('H:i') : '-') . '</td>';
                    $html .= '<td>' . ($timing->end_time ? \Carbon\Carbon::parse($timing->end_time)->format('H:i') : '<span class="badge bg-warning">Running</span>') . '</td>';
                    $html .= '<td>' . ($minutes > 0 ? $minutes . ' min' : '-') . '</td>';
                    $html .= '<td>' . ($timing->measurement_value ?? '-') . '</td>';

                    // Type from measurement_type
                    $typeText = '-';
                    if ($timing->measurement_type == 'qty') {
                        $typeText = 'Qty';
                    } elseif ($timing->measurement_type == 'progress') {
                        $typeText = 'Progress';
                    } elseif ($timing->measurement_type) {
                        $typeText = $timing->measurement_type;
                    }
                    $html .= '<td>' . $typeText . '</td>';

                    // Status badge
                    $statusBadge = '<span class="badge bg-light text-dark">' . ucfirst($timing->status ?? '-') . '</span>';
                    if ($timing->status == 'complete') {
                        $statusBadge = '<span class="badge bg-success">Complete</span>';
                    } elseif ($timing->status == 'on progress') {
                        $statusBadge = '<span class="badge bg-warning">On Progress</span>';
                    } elseif ($timing->status == 'pending') {
                        $statusBadge = '<span class="badge bg-secondary">Pending</span>';
                    }
                    $html .= '<td>' . $statusBadge . '</td>';

                    // Approval badge
                    $approvalBadge = '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>';
                    if ($timing->approval_status == 'approved') {
                        $approvalBadge = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Approved</span>';
                    } elseif ($timing->approval_status == 'rejected') {
                        $approvalBadge = '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>';
                    }
                    $html .= '<td>' . $approvalBadge . '</td>';

                    $html .= '<td>' . ($timing->remarks ?? '-') . '</td>';

                    // Actions column
                    $authUser = auth()->user();
                    $canEdit = $authUser->isSuperAdmin() || $authUser->isLogisticAdmin() || $authUser->id == $timing->employee_id;

                    if ($canEdit) {
                        $editUrl = route('timings.edit', $timing->id);
                        $deleteUrl = route('timings.destroy', $timing->id);
                        $html .= '<td class="text-nowrap">';
                        $html .= '<a href="' . $editUrl . '" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-fill"></i></a> ';
                        $html .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this timing record?\')">';
                        $html .= csrf_field();
                        $html .= method_field('DELETE');
                        $html .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash-fill"></i></button>';
                        $html .= '</form>';
                        $html .= '</td>';
                    } else {
                        $html .= '<td class="text-nowrap"><span class="text-muted">-</span></td>';
                    }

                    $html .= '</tr>';
                }
            }

            return response()->json([
                'html' => $html,
                'count' => $timings->count(),
                'success' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Timing AJAX Search Error: ' . $e->getMessage());
            return response()->json(
                [
                    'html' => '<tr class="no-data-row"><td colspan="14" class="text-center text-muted py-4"><i class="bi bi-exclamation-triangle"></i> Error loading data</td></tr>',
                    'count' => 0,
                    'success' => false,
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function create()
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create timing data.');
        }

        $projects = Project::with(['parts', 'departments'])->get();

        // HANYA ambil employee yang statusnya 'active'
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        $departments = Department::orderBy('name')->pluck('name', 'id');
        return view('production.timings.create', compact('projects', 'employees', 'departments'));
    }

    public function storeMultiple(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('timings.index')->with('error', 'You do not have permission to create timing data.');
        }

        $attributes = [];
        $timings = $request->input('timings', []);
        foreach ($timings as $i => $timing) {
            $row = $i + 1;
            $attributes["timings.$i.tanggal"] = "Date (row $row)";
            $attributes["timings.$i.project_id"] = "Project (row $row)";
            $attributes["timings.$i.step"] = "Step (row $row)";
            $attributes["timings.$i.parts"] = "Part (row $row)";
            $attributes["timings.$i.employee_id"] = "Employee (row $row)";
            $attributes["timings.$i.start_time"] = "Start Time (row $row)";
            $attributes["timings.$i.end_time"] = "End Time (row $row)";
            $attributes["timings.$i.duration_minutes"] = "Duration Minutes (row $row)";
            $attributes["timings.$i.measurement_type"] = "Measurement Type (row $row)";
            $attributes["timings.$i.measurement_value"] = "Measurement Value (row $row)";
            $attributes["timings.$i.status"] = "Status (row $row)";
            $attributes["timings.$i.remarks"] = "Remarks (row $row)";
        }

        $validator = Validator::make(
            $request->all(),
            [
                'timings' => 'required|array',
                'timings.*.tanggal' => 'required|date',
                'timings.*.project_id' => 'required|exists:projects,id',
                'timings.*.step' => 'required',
                'timings.*.parts' => 'nullable|string',
                'timings.*.employee_id' => 'required|exists:employees,id',
                'timings.*.start_time' => 'required',
                'timings.*.end_time' => 'required',
                'timings.*.duration_minutes' => 'required|integer|min:0',
                'timings.*.measurement_type' => 'required|in:progress,qty,pcs,unit',
                'timings.*.measurement_value' => 'required|numeric|min:0',
                'timings.*.status' => 'required|in:complete,on progress,pending',
                'timings.*.remarks' => 'nullable',
            ],
            [],
            $attributes,
        );

        // Validasi custom
        $data = $validator->getData();
        $projectsWithParts = Project::has('parts')->pluck('id')->toArray();
        $projectIds = array_column($request->timings, 'project_id');
        $projects = Project::whereIn('id', $projectIds)->pluck('name', 'id');

        foreach ($data['timings'] as $idx => $timing) {
            // Employee harus aktif
            $employee = Employee::find($timing['employee_id']);
            if (!$employee || $employee->status !== 'active') {
                $validator->errors()->add("timings.$idx.employee_id", 'Selected employee is not active or does not exist.');
            }
            // End time >= start time
            if (isset($timing['start_time'], $timing['end_time'])) {
                if ($timing['end_time'] < $timing['start_time']) {
                    $validator->errors()->add("timings.$idx.end_time", 'End Time (row ' . ($idx + 1) . ') cannot be earlier than start time.');
                }
            }
            // Parts wajib jika project punya parts
            if (in_array($timing['project_id'], $projectsWithParts)) {
                if (empty($timing['parts'])) {
                    $projectName = $projects[$timing['project_id']] ?? 'Unknown';
                    $validator->errors()->add("timings.$idx.parts", "Part is required for project: <b>$projectName</b>");
                }
            }
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Insert ke database
        foreach ($data['timings'] as &$timing) {
            if (!in_array($timing['project_id'], $projectsWithParts)) {
                $timing['parts'] = 'No Part';
            }
            Timing::create($timing);
        }

        return redirect()->route('timings.index')->with('success', 'All timing data is saved successfully.');
    }

    public function show(Timing $timing)
    {
        return view('production.timings.show', compact('timing'));
    }

    public function export(Request $request)
    {
        // Apply same filters as index with eager loading for employee.department
        $query = Timing::with(['project', 'employee.department', 'jobOrder']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('step', 'like', '%' . $request->search . '%')->orWhere('remarks', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('department')) {
            $query->whereHas('project.departments', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $timings = $query->orderByDesc('tanggal')->get();

        // Generate filename based on active filters
        $fileName = 'timing_data';
        $filterParts = [];

        if ($request->filled('search')) {
            $filterParts[] = 'search_' . Str::slug($request->search);
        }
        if ($request->filled('project_id')) {
            $project = Project::find($request->project_id);
            if ($project) {
                $filterParts[] = 'project_' . Str::slug($project->name);
            }
        }
        if ($request->filled('department')) {
            $filterParts[] = 'dept_' . Str::slug($request->department);
        }
        if ($request->filled('employee_id')) {
            $employee = Employee::find($request->employee_id);
            if ($employee) {
                $filterParts[] = 'emp_' . Str::slug($employee->name);
            }
        }

        if (!empty($filterParts)) {
            $fileName .= '_' . implode('_', $filterParts);
        }

        $fileName .= '_' . Carbon::now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new TimingExport($timings), $fileName);
    }

    public function downloadTemplate()
    {
        return Excel::download(new ImportTimingTemplate(), 'timing_template.xlsx');
    }

    public function import(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('timings.index')->with('error', 'You do not have permission to import timing data.');
        }

        $request->validate([
            'xls_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            $import = new TimingImport();
            $data = Excel::toArray($import, $request->file('xls_file'))[0];

            $errors = [];
            $warnings = [];
            $successCount = 0;

            // Skip header row
            $dataRows = array_slice($data, 1);

            foreach ($dataRows as $index => $row) {
                $rowIndex = $index + 2; // +2 karena skip header dan array 0-based

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map columns - NEW ORDER: date, job_order, project, department, step, parts, employee, start, end, duration, value, type, status, approval, remarks
                $tanggal = $row[0] ?? null;
                $jobOrderName = $row[1] ?? null;
                $projectName = $row[2] ?? null;
                $departmentName = $row[3] ?? null;
                $step = $row[4] ?? null;
                $parts = $row[5] ?? null;
                $employeeName = $row[6] ?? null;
                $startTime = $row[7] ?? null;
                $endTime = $row[8] ?? null;
                // $duration = $row[9] ?? null; // Auto-calculated, ignore from import
                $measurementValue = $row[10] ?? null;
                $measurementType = $row[11] ?? null;
                $status = $row[12] ?? null;
                $approvalStatus = $row[13] ?? null;
                $remarks = $row[14] ?? null;

                // Validate required fields
                if (empty($tanggal)) {
                    $errors[] = "Row {$rowIndex}: Date is required";
                    continue;
                }

                // Validate: At least one of job_order or project is required
                if (empty($jobOrderName) && empty($projectName)) {
                    $errors[] = "Row {$rowIndex}: Either Job Order or Project is required";
                    continue;
                }

                if (empty($departmentName)) {
                    $errors[] = "Row {$rowIndex}: Department is required";
                    continue;
                }

                if (empty($employeeName)) {
                    $errors[] = "Row {$rowIndex}: Employee Name is required";
                    continue;
                }
                if (empty($startTime)) {
                    $errors[] = "Row {$rowIndex}: Start Time is required";
                    continue;
                }
                if (empty($endTime)) {
                    $errors[] = "Row {$rowIndex}: End Time is required";
                    continue;
                }

                // Validate date format - Handle multiple formats including DD/MM/YYYY, DD-MM-YYYY
                try {
                    $parsedDate = null;

                    // Handle Excel serial date number (e.g., 44941 for 2023-01-15)
                    if (is_numeric($tanggal) && $tanggal > 25569) {
                        // Excel date: days since 1900-01-01
                        $parsedDate = Carbon::createFromFormat('Y-m-d', '1900-01-01')
                            ->addDays($tanggal - 2) // -2 for Excel leap year bug
                            ->format('Y-m-d');
                    } else {
                        // Try different date formats
                        $dateFormats = [
                            'd/m/Y', // 15/01/2024
                            'd-m-Y', // 15-01-2024
                            'd/m/y', // 15/01/24
                            'd-m-y', // 15-01-24
                            'Y-m-d', // 2024-01-15
                            'Y/m/d', // 2024/01/15
                            'm/d/Y', // 01/15/2024
                            'm-d-Y', // 01-15-2024
                        ];

                        foreach ($dateFormats as $format) {
                            try {
                                $date = Carbon::createFromFormat($format, $tanggal);
                                if ($date && $date->format($format) == $tanggal) {
                                    $parsedDate = $date->format('Y-m-d');
                                    break;
                                }
                            } catch (\Exception $e) {
                                continue;
                            }
                        }

                        // If still not parsed, try Carbon::parse as last resort
                        if (!$parsedDate) {
                            $parsedDate = Carbon::parse($tanggal)->format('Y-m-d');
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowIndex}: Invalid date format '{$tanggal}'. Supported: DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD";
                    continue;
                }

                // Find Job Order if provided
                $jobOrder = null;
                if (!empty($jobOrderName)) {
                    $jobOrder = JobOrder::where('name', $jobOrderName)->first();
                    if (!$jobOrder) {
                        $errors[] = "Row {$rowIndex}: Job Order '{$jobOrderName}' not found";
                        continue;
                    }
                }

                // Find Project if provided (or use job order's project)
                $project = null;
                if (!empty($projectName)) {
                    $project = Project::with('departments')->where('name', $projectName)->first();
                    if (!$project) {
                        $errors[] = "Row {$rowIndex}: Project '{$projectName}' not found";
                        continue;
                    }
                } elseif ($jobOrder && $jobOrder->project_id) {
                    // If no project but have job order, use job order's project
                    $project = Project::with('departments')->find($jobOrder->project_id);
                }

                // Validate we have at least project
                if (!$project) {
                    $errors[] = "Row {$rowIndex}: Could not determine project from provided data";
                    continue;
                }

                // Find Department
                $department = Department::where('name', $departmentName)->first();
                if (!$department) {
                    $errors[] = "Row {$rowIndex}: Department '{$departmentName}' not found";
                    continue;
                }

                // Find Employee
                $employee = Employee::where('name', $employeeName)->first();
                if (!$employee) {
                    $errors[] = "Row {$rowIndex}: Employee '{$employeeName}' not found";
                    continue;
                }

                // Check if employee is active
                if ($employee->status !== 'active') {
                    $errors[] = "Row {$rowIndex}: Employee '{$employeeName}' is not active";
                    continue;
                }

                // Validate and parse time format with detailed error messages
                $startTimeParsed = null;
                $endTimeParsed = null;

                // Parse start time
                if (empty($startTime)) {
                    $errors[] = "Row {$rowIndex}: Start time is empty";
                    continue;
                } else {
                    $startTimeParsed = $this->parseTimeFormat($startTime, $rowIndex, 'Start', $errors);
                    if ($startTimeParsed === false) {
                        continue; // Error already added to $errors array
                    }
                }

                // Parse end time
                if (empty($endTime)) {
                    $errors[] = "Row {$rowIndex}: End time is empty";
                    continue;
                } else {
                    $endTimeParsed = $this->parseTimeFormat($endTime, $rowIndex, 'End', $errors);
                    if ($endTimeParsed === false) {
                        continue; // Error already added to $errors array
                    }
                }

                // Validate time logic (end time should be after start time)
                if ($startTimeParsed && $endTimeParsed) {
                    $startCompare = Carbon::createFromFormat('H:i:s', $startTimeParsed);
                    $endCompare = Carbon::createFromFormat('H:i:s', $endTimeParsed);

                    if ($endCompare->lessThanOrEqualTo($startCompare)) {
                        $errors[] = "Row {$rowIndex}: End time ({$endTime}) must be after start time ({$startTime})";
                        continue;
                    }
                }

                // Validate status if provided
                if (!empty($status) && !in_array($status, ['complete', 'on progress', 'pending'])) {
                    $errors[] = "Row {$rowIndex}: Status must be one of: complete, on progress, pending";
                    continue;
                }

                // Set default status if empty
                if (empty($status)) {
                    $status = 'pending';
                }

                // Validate approval status if provided
                if (!empty($approvalStatus) && !in_array($approvalStatus, ['pending', 'approved', 'rejected'])) {
                    $errors[] = "Row {$rowIndex}: Approval status must be one of: pending, approved, rejected";
                    continue;
                }

                // Set default approval status if empty
                if (empty($approvalStatus)) {
                    $approvalStatus = 'pending';
                }

                // Create timing record
                try {
                    // Debug logging for time values
                    Log::info('Timing Import Debug', [
                        'row' => $rowIndex,
                        'original_start' => $startTime,
                        'original_end' => $endTime,
                        'parsed_start' => $startTimeParsed,
                        'parsed_end' => $endTimeParsed,
                    ]);

                    $timing = Timing::create([
                        'tanggal' => $parsedDate,
                        'job_order_id' => $jobOrder ? $jobOrder->id : null,
                        'project_id' => $project->id,
                        'step' => $step,
                        'parts' => $parts ?? 'No Part',
                        'employee_id' => $employee->id,
                        'start_time' => $startTimeParsed,
                        'end_time' => $endTimeParsed,
                        'measurement_value' => $measurementValue ?? 0,
                        'measurement_type' => $measurementType ?? 'pcs',
                        'status' => $status,
                        'approval_status' => $approvalStatus,
                        'remarks' => $remarks,
                    ]);

                    // Verify data was saved correctly
                    if ($timing->start_time && $timing->end_time) {
                        $successCount++;
                    } else {
                        $warnings[] = "Row {$rowIndex}: Data saved but time fields may be empty";
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowIndex}: Failed to save data - " . $e->getMessage();
                    Log::error('Timing Import Error', [
                        'row' => $rowIndex,
                        'error' => $e->getMessage(),
                        'data' => [
                            'tanggal' => $parsedDate,
                            'project_id' => $project->id,
                            'start_time' => $startTimeParsed,
                            'end_time' => $endTimeParsed,
                        ],
                    ]);
                }
            }

            $message = "Successfully imported {$successCount} timing records.";

            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' errors occurred.';
                Log::error('Timing Import Errors', $errors);
            }

            if (!empty($warnings)) {
                $message .= ' ' . count($warnings) . ' warnings.';
                Log::warning('Timing Import Warnings', $warnings);
            }

            return redirect()
                ->route('timings.index')
                ->with([
                    'success' => $message,
                    'errors' => $errors,
                    'warnings' => $warnings,
                ]);
        } catch (\Exception $e) {
            Log::error('Timing Import Exception', ['error' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Failed to import file: ' . $e->getMessage());
        }
    }

    /**
     * Parse time format from various formats
     * Returns formatted time string (H:i:s) or false on failure
     */
    private function parseTimeFormat($timeString, $rowIndex, $timeType, &$errors)
    {
        // Clean the time string
        $timeString = trim($timeString);

        if (empty($timeString)) {
            $errors[] = "Row {$rowIndex}: {$timeType} time is empty";
            return false;
        }

        // Handle Excel time format (decimal number representing fraction of day)
        if (is_numeric($timeString) && $timeString >= 0 && $timeString <= 1) {
            try {
                // Convert Excel decimal time to hours:minutes:seconds
                $totalSeconds = $timeString * 24 * 3600;
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            } catch (\Exception $e) {
                // Continue to other parsing methods
            }
        }

        // Try different time formats
        $formats = [
            'H:i:s', // 13:30:00
            'H:i', // 13:30
            'g:i:s A', // 1:30:00 PM
            'g:i A', // 1:30 PM
            'h:i:s A', // 01:30:00 PM
            'h:i A', // 01:30 PM
            'G:i:s', // 13:30:00 (without leading zeros)
            'G:i', // 13:30 (without leading zeros)
            'g.i A', // 1.30 PM (dot separator)
            'H.i', // 13.30 (dot separator)
            'H-i', // 13-30 (dash separator)
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $timeString);
                if ($parsed && $parsed->format($format) == $timeString) {
                    return $parsed->format('H:i:s');
                }
            } catch (\Exception $e) {
                // Continue to next format
                continue;
            }
        }

        // If all formats fail, try Carbon::parse as fallback
        try {
            $parsed = Carbon::parse($timeString);
            if ($parsed) {
                return $parsed->format('H:i:s');
            }
        } catch (\Exception $e) {
            // Final fallback failed
        }

        // All parsing attempts failed
        $errors[] = "Row {$rowIndex}: Invalid {$timeType} time format '{$timeString}'. Expected formats: HH:MM, HH:MM:SS, H:MM AM/PM, etc.";
        return false;
    }

    public function edit(Timing $timing)
    {
        $projects = Project::with(['parts', 'departments'])->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $departments = Department::orderBy('name')->pluck('name', 'id');

        return view('production.timings.edit', compact('timing', 'projects', 'employees', 'departments'));
    }

    public function update(Request $request, Timing $timing)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('timings.index')->with('error', 'You do not have permission to update timing data.');
        }

        $attributes = [
            'tanggal' => 'Date',
            'project_id' => 'Project',
            'step' => 'Step',
            'parts' => 'Part',
            'employee_id' => 'Employee',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'duration_minutes' => 'Duration Minutes',
            'measurement_type' => 'Measurement Type',
            'measurement_value' => 'Measurement Value',
            'status' => 'Status',
            'remarks' => 'Remarks',
        ];

        $validator = Validator::make(
            $request->all(),
            [
                'tanggal' => 'required|date',
                'project_id' => 'required|exists:projects,id',
                'step' => 'required',
                'parts' => 'nullable|string',
                'employee_id' => 'required|exists:employees,id',
                'start_time' => 'required',
                'end_time' => 'required',
                'duration_minutes' => 'required|integer|min:0',
                'measurement_type' => 'required|in:progress,qty,pcs,unit',
                'measurement_value' => 'required|numeric|min:0',
                'status' => 'required|in:complete,on progress,pending',
                'remarks' => 'nullable',
            ],
            [],
            $attributes,
        );

        // Custom validation
        $project = Project::find($request->project_id);
        $projectsWithParts = Project::has('parts')->pluck('id')->toArray();

        if ($project && in_array($project->id, $projectsWithParts) && empty($request->parts)) {
            $validator->errors()->add('parts', 'Part is required for this project.');
        }

        $employee = Employee::find($request->employee_id);
        if (!$employee || $employee->status !== 'active') {
            $validator->errors()->add('employee_id', 'Selected employee is not active or does not exist.');
        }

        if ($request->start_time && $request->end_time && $request->end_time < $request->start_time) {
            $validator->errors()->add('end_time', 'End Time cannot be earlier than Start Time.');
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        if (!in_array($data['project_id'], $projectsWithParts)) {
            $data['parts'] = 'No Part';
        }

        $timing->update($data);

        return redirect()->route('timings.index')->with('success', 'Timing data updated successfully.');
    }

    public function destroy(Timing $timing)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('timings.index')->with('error', 'Only super admin can delete timing data.');
        }

        try {
            $timing->delete();
            return redirect()->route('timings.index')->with('success', 'Timing data deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('timings.index')
                ->with('error', 'Failed to delete timing data: ' . $e->getMessage());
        }
    }
}
