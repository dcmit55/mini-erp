<?php

namespace App\Http\Controllers;

use App\Models\InternalProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InternalProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $projectType = $request->input('project_type');

        $projects = InternalProject::query()
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

        return view('internal-projects.create', compact('projectTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project' => 'required|string|in:Office,Machine,Testing,Facilities',
            'job' => 'required|string|max:200',
            'description' => 'nullable|string',
            // PIC dihapus dari validation karena akan auto diambil
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Get current user
            $user = Auth::user();
            
            // Create new project
            $project = new InternalProject();
            $project->project = $request->project;
            $project->job = $request->job;
            $project->description = $request->description;
            
            // Auto set PIC dari user yang login
            if ($user) {
                $project->pic = $user->id; // Simpan user ID
                $project->update_by = $user->id;
            }
            
            // Simpan
            $project->save();

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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $internalProject = InternalProject::findOrFail($id);
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
            $internalProject = InternalProject::findOrFail($id); // Changed to $internalProject
            $projectTypes = [
                'Office' => 'Office',
                'Machine' => 'Machine',
                'Testing' => 'Testing',
                'Facilities' => 'Facilities',
            ];

            return view('internal-projects.edit', compact('internalProject', 'projectTypes'));
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
            'project' => 'required|string|in:Office,Machine,Testing,Facilities',
            'job' => 'required|string|max:200',
            'description' => 'nullable|string',
            // PIC tidak perlu di update
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $internalProject = InternalProject::findOrFail($id); // Changed to $internalProject
            
            // Get current user untuk update_by
            $user = Auth::user();
            
            $internalProject->project = $request->project;
            $internalProject->job = $request->job;
            $internalProject->description = $request->description;
            
            // Update update_by dengan user yang login
            if ($user) {
                $internalProject->update_by = $user->id;
            }
            
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

            $internalProject = InternalProject::findOrFail($id); // Changed to $internalProject
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