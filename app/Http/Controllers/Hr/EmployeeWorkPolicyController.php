<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeWorkPolicy;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class EmployeeWorkPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Admin HR, Super Admin, dan Admin (read-only) bisa akses
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_hr', 'admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized access to HR module.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of work policies.
     */
    public function index(Request $request)
    {
        // Hanya super_admin dan admin_hr yang bisa melihat daftar semua policy
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to view all work policies.');
        }

        $query = EmployeeWorkPolicy::with('employee');

        // Filter by employee name or number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        $policies = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('hr.employee-work-policies.index', compact('policies'));
    }

    /**
     * Show the form for creating a new work policy.
     */
    public function create()
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create work policies.');
        }

        // Ambil daftar employee yang belum memiliki policy
        $employees = Employee::whereDoesntHave('workPolicy')
            ->orderBy('name')
            ->get(['id', 'employee_no', 'name']);

        return view('hr.employee-work-policies.create', compact('employees'));
    }

    /**
     * Store a newly created work policy.
     */
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create work policies.');
        }

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,id',
                Rule::unique('employee_work_policies')->whereNull('deleted_at')
            ],
            'weekday_hours' => 'required|numeric|min:0|max:24',
            'saturday_hours' => 'required|numeric|min:0|max:24',
        ]);

        // Ambil data employee untuk mendapatkan employee_no
        $employee = Employee::findOrFail($validated['employee_id']);

        $policy = EmployeeWorkPolicy::create([
            'uid' => Str::uuid(),
            'employee_id' => $validated['employee_id'],
            'employee_no' => $employee->employee_no,
            'weekday_hours' => $validated['weekday_hours'],
            'saturday_hours' => $validated['saturday_hours'],
        ]);

        return redirect()->route('employee-work-policies.index')
            ->with('success', 'Work policy created successfully for ' . $employee->name);
    }

    /**
     * Show the form for editing the specified work policy.
     */
    public function edit(EmployeeWorkPolicy $policy)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to edit work policies.');
        }

        $policy->load('employee');

        return view('hr.employee-work-policies.edit', compact('policy'));
    }

    /**
     * Update the specified work policy.
     */
    public function update(Request $request, EmployeeWorkPolicy $policy)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to update work policies.');
        }

        $validated = $request->validate([
            'weekday_hours' => 'required|numeric|min:0|max:24',
            'saturday_hours' => 'required|numeric|min:0|max:24',
        ]);

        $policy->update($validated);

        return redirect()->route('employees.show', $policy->employee_id)
            ->with('success', 'Work policy updated successfully.');
    }

    /**
     * Remove the specified work policy (soft delete).
     */
    public function destroy(EmployeeWorkPolicy $policy)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to delete work policies.');
        }

        $employeeId = $policy->employee_id;
        $policy->delete();

        return redirect()->route('employees.show', $employeeId)
            ->with('success', 'Work policy deleted successfully.');
    }

    /**
     * API endpoint untuk mendapatkan jam kerja karyawan berdasarkan hari (opsional)
     */
    public function getHours(Employee $employee)
    {
        try {
            $policy = $employee->workPolicy;
            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No work policy found for this employee.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'weekday_hours' => $policy->weekday_hours,
                    'saturday_hours' => $policy->saturday_hours,
                    'weekly_hours' => $policy->weekly_hours,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve work hours.',
            ], 500);
        }
    }
}