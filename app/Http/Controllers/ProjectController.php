<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Models\Project;
use App\Models\Department;
use App\Models\Part;
use App\Models\ProjectStatus;
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
        $query = Project::with('department', 'status');

        // Apply Filters
        if ($request->has('quantity') && $request->quantity !== null) {
            $query->where('qty', $request->quantity);
        }

        // Filter by department name (bukan id)
        if ($request->has('department') && $request->department !== null) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }

        $projects = $query->latest()->get();
        $departments = Department::orderBy('name')->get(); // Tambahkan baris ini

        return view('projects.index', compact('projects', 'departments'));
    }

    public function export(Request $request)
    {
        // Ambil filter dari request
        $quantity = $request->quantity;
        $department = $request->department;

        // Filter data berdasarkan request
        $query = Project::query();

        if ($quantity) {
            $query->where('qty', $quantity);
        }

        if ($department) {
            $query->where('department', $department);
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
        return view('projects.create', compact('departments', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:projects,name,NULL,id,deleted_at,NULL',
            'qty' => 'required|integer|min:1',
            'img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'finish_date' => 'nullable|date',
            'department_id' => 'required|exists:departments,id',
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:projects,name,NULL,id,deleted_at,NULL',
            'qty' => 'nullable|numeric|min:0',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                // Kirim error pertama atau semua error
                return response()->json(
                    [
                        'success' => false,
                        'message' => $validator->errors()->first(), // atau implode(', ', $validator->all())
                    ],
                    422,
                );
            }
            return back()->withErrors($validator)->withInput();
        }

        $project = Project::create([
            'name' => $request->name,
            'qty' => $request->qty,
            'department_id' => $request->department_id,
            'created_by' => Auth::user()->username,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'project' => $project]);
        }

        return back()->with('success', 'Project added successfully!');
    }

    public function json()
    {
        // return Project::select('id', 'name')->get();
        return response()->json(Project::select('id', 'name')->get()); // bisa juga pakai paginate/dataTables untuk ribuan data
    }

    public function edit(Project $project)
    {
        $project->load('parts');
        $departments = Department::orderBy('name')->get();
        $statuses = ProjectStatus::orderBy('name')->get();
        return view('projects.edit', compact('project', 'departments', 'statuses'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:projects,name,' . $project->id . ',id,deleted_at,NULL',
            'qty' => 'required|integer|min:1',
            'img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'finish_date' => 'nullable|date',
            'department_id' => 'required|exists:departments,id',
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

        // Update parts: hapus semua lalu simpan ulang
        $project->parts()->delete();
        if ($request->parts) {
            foreach ($request->parts as $part) {
                if ($part) {
                    $project->parts()->create(['part_name' => $part]);
                }
            }
        }

        // Tambahkan baris berikut agar redirect ke modul project
        return redirect()->route('projects.index')->with('success', 'Project updated successfully!');
    }
    public function destroy(Project $project)
    {
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
