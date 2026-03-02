<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Department;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || !in_array($user->role, ['super_admin', 'admin'])) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $departments = Department::all();
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(Request $request)
    {
        // Handle AJAX request for quick add
        if ($request->ajax()) {
            if (Auth::user()->isReadOnlyAdmin()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'You do not have permission to create departments.',
                    ],
                    403,
                );
            }

            $name = $request->input('department_name');
            $exists = Department::where('name', $name)->exists();

            if ($exists) {
                $msg = "Department '$name' already exists.";
                return response()->json(['message' => $msg], 422);
            }

            $validated = $request->validate([
                'department_name' => 'required|string|max:255|unique:departments,name',
            ]);
            $department = Department::create(['name' => $validated['department_name']]);

            return response()->json($department);
        }

        // Handle normal form submission
        $request->validate(
            [
                'name' => 'required|string|max:255|unique:departments,name',
            ],
            [
                'name.required' => 'Department name is required.',
                'name.unique' => 'Department name already exists.',
            ],
        );

        $department = Department::create([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', "Department '{$department->name}' created successfully.");
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $request->validate(
            [
                'name' => 'required|string|max:255|unique:departments,name,' . $id,
            ],
            [
                'name.required' => 'Department name is required.',
                'name.unique' => 'Department name already exists.',
            ],
        );

        $department->update([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', "Department '{$department->name}' updated successfully.");
    }

    public function destroy(Department $department)
    {
        $departmentName = $department->name;

        // Check if department is being used by any users
        if ($department->users()->count() > 0) {
            return redirect()
                ->route('departments.index')
                ->with('error', "Cannot delete department '{$departmentName}' because it has associated users.");
        }

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', "Department '{$departmentName}' deleted successfully.");
    }
}
