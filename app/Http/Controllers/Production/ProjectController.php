<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use App\Models\Part;
use App\Models\Production\ProjectStatus;
use Illuminate\Http\Request;
use App\Exports\ProjectExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Project::with('departments', 'status');

        // Apply Filters
        if ($request->has('quantity') && $request->quantity !== null) {
            $query->where('qty', $request->quantity);
        }

        // Filter by department (multi-department support)
        if ($request->has('department') && $request->department !== null) {
            $departmentFilter = $request->department;

            // Check if it's numeric (ID) or string (name)
            if (is_numeric($departmentFilter)) {
                $query->whereHas('departments', function ($q) use ($departmentFilter) {
                    $q->where('departments.id', $departmentFilter);
                });
            } else {
                $query->whereHas('departments', function ($q) use ($departmentFilter) {
                    $q->where('departments.name', $departmentFilter);
                });
            }
        }

        // filter by status
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('project_status_id', $request->status);
        }

        $projects = $query->latest()->get();
        $departments = Department::orderBy('name')->get();
        $allQuantities = Project::pluck('qty')->unique()->sort()->values();
        $statuses = ProjectStatus::orderBy('name')->get();

        return view('production.projects.index', compact('projects', 'departments', 'allQuantities', 'statuses'));
    }

    public function export(Request $request)
    {
        // Ambil filter dari request
        $quantity = $request->quantity;
        $department = $request->department;

        // Filter data berdasarkan request
        $query = Project::with('departments');

        if ($quantity) {
            $query->where('qty', $quantity);
        }

        if ($department) {
            if (is_numeric($department)) {
                $query->whereHas('departments', function ($q) use ($department) {
                    $q->where('departments.id', $department);
                });
            } else {
                $query->whereHas('departments', function ($q) use ($department) {
                    $q->where('departments.name', $department);
                });
            }
        }

        $projects = $query->get();

        // Buat nama file dinamis
        $fileName = 'projects';
        if ($quantity) {
            $fileName .= '_quantity-' . $quantity;
        }
        if ($department) {
            $fileName .= '_department-' . str_replace('&', 'and', strtolower($department));
        }
        $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

        // Ekspor data menggunakan kelas ProjectExport
        return Excel::download(new ProjectExport($projects), $fileName);
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $statuses = ProjectStatus::orderBy('name')->get();
        return view('production.projects.create', compact('departments', 'statuses'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create projects.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:projects,name,NULL,id,deleted_at,NULL',
            'qty' => 'required|integer|min:1',
            'img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'finish_date' => 'nullable|date',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'project_status_id' => 'required|exists:project_statuses,id',
        ]);

        if ($request->hasFile('img')) {
            $validated['img'] = $request->file('img')->store('projects', 'public');
        }

        $project = Project::create(
            array_merge($validated, [
                'created_by' => Auth::user()->username,
            ]),
        );

        // Attach departments
        $project->departments()->attach($request->department_ids);

        // Simpan parts jika ada
        if ($request->parts) {
            foreach ($request->parts as $part) {
                if ($part) {
                    $project->parts()->create(['part_name' => $part]);
                }
            }
        }

        return redirect()->route('projects.index')->with('success', 'Project added successfully!');
    }

    public function storeQuick(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create projects.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:projects,name,NULL,id,deleted_at,NULL',
            'qty' => 'nullable|numeric|min:0',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $project = Project::create([
            'name' => $request->name,
            'qty' => $request->qty,
            'created_by' => Auth::user()->username,
        ]);

        // Attach departments
        $project->departments()->attach($request->department_ids);

        return response()->json(['success' => true, 'project' => $project]);
    }

    public function json()
    {
        return response()->json(Project::select('id', 'name')->get());
    }

    public function edit(Project $project)
    {
        $project->load('parts', 'departments');
        $departments = Department::orderBy('name')->get();
        $statuses = ProjectStatus::orderBy('name')->get();
        return view('production.projects.edit', compact('project', 'departments', 'statuses'));
    }

    public function update(Request $request, Project $project)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to update projects.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:projects,name,' . $project->id . ',id,deleted_at,NULL',
            'qty' => 'required|integer|min:1',
            'img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'finish_date' => 'nullable|date',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'project_status_id' => 'required|exists:project_statuses,id',
        ]);

        // Validasi: start_date tidak boleh melebihi deadline
        if ($request->start_date && $request->deadline && $request->start_date > $request->deadline) {
            return back()
                ->withErrors(['start_date' => 'Start Date cannot be later than Deadline.'])
                ->withInput();
        }

        if ($request->hasFile('img')) {
            $validated['img'] = $request->file('img')->store('projects', 'public');
        }

        $project->update($validated);

        // Sync departments
        $project->departments()->sync($request->department_ids);

        // Audit perubahan parts
        $oldParts = $project->parts()->pluck('part_name')->toArray();
        $project->parts()->delete();
        $newParts = [];
        if ($request->parts) {
            foreach ($request->parts as $part) {
                if ($part) {
                    $project->parts()->create(['part_name' => $part]);
                    $newParts[] = $part;
                }
            }
        }

        // Manual audit untuk perubahan parts
        if ($oldParts !== $newParts) {
            \OwenIt\Auditing\Models\Audit::create([
                'user_id' => Auth::id(),
                'auditable_type' => Project::class,
                'auditable_id' => $project->id,
                'event' => 'updated',
                'old_values' => ['parts' => $oldParts],
                'new_values' => ['parts' => $newParts],
                'url' => url()->current(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return redirect()->route('projects.index')->with('success', 'Project updated successfully!');
    }

    public function destroy(Project $project)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to update projects.');
        }

        // Validasi: Hanya pembuat proyek atau super_admin yang dapat menghapus
        if (Auth::user()->username !== $project->created_by && Auth::user()->role !== 'super_admin') {
            return redirect()->route('projects.index')->with('error', 'You are not authorized to delete this project.');
        }

        $projectName = $project->name;
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', "Project <b>{$projectName}</b> deleted successfully!");
    }
}
