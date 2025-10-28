<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Timing;
use App\Models\Project;
use App\Models\Employee;
use App\Models\Department;
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
        $timings = Timing::with(['project.department', 'employee.department'])
            ->latest()
            ->get();

        $projects = Project::with('department')->orderBy('name')->get();
        $departments = Department::orderBy('name')->pluck('name', 'id');
        $employees = Employee::orderBy('name')->get();

        return view('timings.index', compact('timings', 'projects', 'departments', 'employees'));
    }

    public function ajaxSearch(Request $request)
    {
        $query = Timing::with(['project.department', 'employee.department']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('step', 'like', '%' . $request->search . '%')->orWhere('remarks', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('department')) {
            $query->whereHas('project.department', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $timings = $query->orderByDesc('tanggal')->get();

        try {
            $html = view('timings.timing_table', compact('timings'))->render();
            return response()->json([
                'html' => $html,
                'count' => $timings->count(),
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'html' => '<tr class="no-data-row"><td colspan="11" class="text-center text-muted py-4"><i class="bi bi-exclamation-triangle"></i> Error loading data</td></tr>',
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

        $projects = Project::with(['parts', 'department'])->get();

        // HANYA ambil employee yang statusnya 'active'
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        $departments = Department::orderBy('name')->pluck('name', 'id');
        return view('timings.create', compact('projects', 'employees', 'departments'));
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
            $attributes["timings.$i.output_qty"] = "Output Qty (row $row)";
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
                'timings.*.output_qty' => 'required|numeric|min:0',
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
        return view('timings.show', compact('timing'));
    }

    public function export(Request $request)
    {
        // Apply same filters as index with eager loading for employee.department
        $query = Timing::with(['project.department', 'employee.department']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('step', 'like', '%' . $request->search . '%')->orWhere('remarks', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('department')) {
            $query->whereHas('project.department', function ($q) use ($request) {
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

                // Map columns - Sinkron dengan urutan file import: date, project, department, step, part, employee, start, end, qty, status, remark
                $tanggal = $row[0] ?? null;
                $projectName = $row[1] ?? null;
                $departmentName = $row[2] ?? null;
                $step = $row[3] ?? null;
                $parts = $row[4] ?? null;
                $employeeName = $row[5] ?? null;
                $startTime = $row[6] ?? null;
                $endTime = $row[7] ?? null;
                $outputQty = $row[8] ?? null;
                $status = $row[9] ?? null;
                $remarks = $row[10] ?? null;

                // Validate required fields
                if (empty($tanggal)) {
                    $errors[] = "Row {$rowIndex}: Date is required";
                    continue;
                }
                if (empty($projectName)) {
                    $errors[] = "Row {$rowIndex}: Project Name is required";
                    continue;
                }
                if (empty($step)) {
                    $errors[] = "Row {$rowIndex}: Step is required";
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
                if (empty($outputQty) || !is_numeric($outputQty)) {
                    $errors[] = "Row {$rowIndex}: Output Qty must be a valid number";
                    continue;
                }
                if (empty($status) || !in_array($status, ['complete', 'on progress', 'pending'])) {
                    $errors[] = "Row {$rowIndex}: Status must be one of: complete, on progress, pending";
                    continue;
                }

                // Validate date format
                try {
                    $parsedDate = Carbon::parse($tanggal)->format('Y-m-d');
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowIndex}: Invalid date format";
                    continue;
                }

                // Find Project with department relationship
                $project = Project::with('department')->where('name', $projectName)->first();
                if (!$project) {
                    $errors[] = "Row {$rowIndex}: Project '{$projectName}' not found";
                    continue;
                }

                // Get department from project
                $projectDepartment = $project->department ? $project->department->name : null;
                if (!$projectDepartment) {
                    $errors[] = "Row {$rowIndex}: Project '{$projectName}' does not have a department assigned";
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

                // Department will be taken from project, not from import data or employee
                // Note: Department is not stored in timings table, it's accessed via project relationship
                if (!empty($departmentName) && $departmentName !== $projectDepartment) {
                    $warnings[] = "Row {$rowIndex}: Department '{$departmentName}' from import will be ignored. Using project department '{$projectDepartment}'.";
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

                // Check if project has parts and parts is required
                $projectsWithParts = Project::has('parts')->pluck('id')->toArray();
                if (in_array($project->id, $projectsWithParts) && empty($parts)) {
                    $errors[] = "Row {$rowIndex}: Parts is required for project '{$projectName}'";
                    continue;
                }

                // If project doesn't have parts, set default
                if (!in_array($project->id, $projectsWithParts)) {
                    $parts = 'No Part';
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
                        'project_id' => $project->id,
                        'step' => $step,
                        'parts' => $parts,
                        'employee_id' => $employee->id,
                        'start_time' => $startTimeParsed,
                        'end_time' => $endTimeParsed,
                        'output_qty' => $outputQty,
                        'status' => $status,
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
}
