<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Timing;
use App\Models\Project;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $timings = Timing::with(['project.department', 'employee'])
            ->orderByDesc('created_at')
            ->get();

        $projects = Project::with('department')->orderBy('name')->get();
        $departments = Department::orderBy('name')->pluck('name', 'id');
        $employees = Employee::orderBy('name')->get();

        return view('timings.index', compact('timings', 'projects', 'departments', 'employees'));
    }

    public function ajaxSearch(Request $request)
    {
        $query = Timing::with(['project.department', 'employee']);

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
}
