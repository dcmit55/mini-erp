<?php

namespace App\Http\Controllers;

use App\Models\InternalProject;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InternalProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $projectType = $request->input('project_type');

        $projects = InternalProject::with(['picUser', 'updateUser', 'department'])
            ->when($search, function($query) use ($search) {
                $query->where('job', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('project', 'like', '%' . $search . '%')
                    ->orWhere('department', 'like', '%' . $search . '%');
            })
            ->when($projectType, function($query) use ($projectType) {
                $query->where('project', $projectType);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('internal-projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projectTypes = [
            'Office' => 'Office',
            'Machine' => 'Machine',
            'Testing' => 'Testing',
            'Facilities' => 'Facilities',
        ];

        $departments = Department::orderBy('name')->get();

        // Cari department PT DCM untuk default value
        $ptDcmDepartment = Department::where('name', 'PT DCM')->first();
        if (!$ptDcmDepartment) {
            $ptDcmDepartment = Department::orderBy('id')->first();
        }
        $defaultPtDcmDepartmentId = $ptDcmDepartment ? $ptDcmDepartment->id : null;

        return view('internal-projects.create', compact('projectTypes', 'departments', 'defaultPtDcmDepartmentId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project'       => 'required|string|in:Office,Machine,Testing,Facilities',
            'job'           => 'required|string|max:200',
            'description'   => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $department = Department::find($request->department_id);
            $departmentName = $department ? $department->name : 'PT DCM';

            $project = InternalProject::create([
                'project'       => $request->project,
                'job'           => $request->job,
                'description'   => $request->description,
                'department'    => $departmentName,
                'department_id' => $request->department_id,
                'pic'           => auth()->id(),
                'update_by'     => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('internal-projects.index')
                ->with('success', 'Internal project created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create project: ' . $e->getMessage());
        }
    }

    /**
     * Quick store internal project from modal (AJAX) – Digunakan di halaman Create Material Request.
     */
    public function quickStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project'       => 'required|in:Office,Machine,Testing,Facilities',
            'department_id' => 'required|exists:departments,id',
            'job'           => 'required|string|max:200',
            'description'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $department = Department::find($request->department_id);
            $departmentName = $department ? $department->name : 'PT DCM';

            $internalProject = InternalProject::create([
                'project'       => $request->project,
                'job'           => $request->job,
                'description'   => $request->description,
                'department'    => $departmentName,
                'department_id' => $request->department_id,
                'pic'           => auth()->id(),
                'update_by'     => auth()->id(),
                'uid'           => Str::uuid(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Internal project added successfully!',
                'internal_project' => [
                    'id'      => $internalProject->id,
                    'project' => $internalProject->project,
                    'job'     => $internalProject->job,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add internal project: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $internalProject = InternalProject::with(['picUser', 'updateUser', 'department'])->findOrFail($id);
            return view('internal-projects.show', compact('internalProject'));
        } catch (\Exception $e) {
            return redirect()
                ->route('internal-projects.index')
                ->with('error', 'Project not found!');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $internalProject = InternalProject::findOrFail($id);
            $projectTypes = [
                'Office' => 'Office',
                'Machine' => 'Machine',
                'Testing' => 'Testing',
                'Facilities' => 'Facilities',
            ];

            $departments = Department::orderBy('name')->get();

            $ptDcmDepartment = Department::where('name', 'PT DCM')->first();
            if (!$ptDcmDepartment) {
                $ptDcmDepartment = Department::orderBy('id')->first();
            }
            $defaultPtDcmDepartmentId = $ptDcmDepartment ? $ptDcmDepartment->id : null;

            return view('internal-projects.edit', compact('internalProject', 'projectTypes', 'departments', 'defaultPtDcmDepartmentId'));
        } catch (\Exception $e) {
            return redirect()
                ->route('internal-projects.index')
                ->with('error', 'Project not found!');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'project'       => 'required|string|in:Office,Machine,Testing,Facilities',
            'job'           => 'required|string|max:200',
            'description'   => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $internalProject = InternalProject::findOrFail($id);
            $user = Auth::user();

            $department = Department::find($request->department_id);
            $departmentName = $department ? $department->name : 'PT DCM';

            $internalProject->project       = $request->project;
            $internalProject->job           = $request->job;
            $internalProject->description   = $request->description;
            $internalProject->department    = $departmentName;
            $internalProject->department_id = $request->department_id;
            $internalProject->update_by     = $user->id;

            $internalProject->save();

            DB::commit();

            return redirect()
                ->route('internal-projects.index')
                ->with('success', 'Internal project updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update project: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $internalProject = InternalProject::findOrFail($id);
            $internalProject->delete();

            DB::commit();

            return redirect()
                ->route('internal-projects.index')
                ->with('success', 'Internal project has been PERMANENTLY deleted!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('internal-projects.index')
                ->with('error', 'Failed to delete project: ' . $e->getMessage());
        }
    }
}