<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;    
use App\Models\Production\Project;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;

class ProjectApiController extends Controller
{
    /**
     * Get projects untuk dropdown (hanya field yang dibutuhkan)
     * Return: id, name, departments, parts
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjects(Request $request)
    {
        try {
            $query = Project::with(['departments:id,name', 'parts:id,part_name,project_id']);

            // Optional filter by status
            if ($request->has('status_id')) {
                $query->where('project_status_id', $request->status_id);
            }

            // Optional filter by department
            if ($request->has('department_id')) {
                $query->whereHas('departments', function($q) use ($request) {
                    $q->where('departments.id', $request->department_id);
                });
            }

            $projects = $query->select('id', 'name', 'department_id')
                ->orderBy('name')
                ->get()
                ->map(function($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'departments' => $project->departments->map(function($dept) {
                            return [
                                'id' => $dept->id,
                                'name' => $dept->name
                            ];
                        }),
                        'parts' => $project->parts->map(function($part) {
                            return [
                                'id' => $part->id,
                                'name' => $part->part_name
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $projects
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single project by ID dengan departments dan parts
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectById($id)
    {
        try {
            $project = Project::with(['departments:id,name', 'parts:id,part_name,project_id'])
                ->select('id', 'name', 'department_id')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'departments' => $project->departments->map(function($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name
                        ];
                    }),
                    'parts' => $project->parts->map(function($part) {
                        return [
                            'id' => $part->id,
                            'name' => $part->part_name
                        ];
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }
    }

    /**
     * Get employees untuk dropdown (hanya id dan name)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployees(Request $request)
    {
        try {
            $query = Employee::query();

            // Filter by department
            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            // Active employees only
            if ($request->has('active_only') && $request->active_only == 'true') {
                $query->where('is_active', true);
            }

            $employees = $query->select('id', 'name', 'department_id', 'position')
                ->with(['department:id,name'])
                ->orderBy('name')
                ->get()
                ->map(function($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'department' => $employee->department ? $employee->department->name : null,
                        'department_id' => $employee->department_id,
                        'position' => $employee->position
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $employees
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}