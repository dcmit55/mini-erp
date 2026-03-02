<?php
// app/Http/Controllers/Hr/EmployeeWorkPolicyController.php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmployeeWorkPolicy;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\WorkPoliciesImport;

class EmployeeWorkPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::user()->isReadOnlyAdmin()) {
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
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to view all work policies.');
        }

        $query = EmployeeWorkPolicy::with('employee');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $policies = $query->latest()->paginate(15);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('hr.employee-work-policies.index', compact('policies', 'departments'));
    }

    /**
     * Show the form for creating a new work policy.
     */
    public function create()
    {
        $employees = Employee::where('status', 'active')
            ->whereDoesntHave('workPolicy')
            ->orderBy('name')
            ->get(['id', 'employee_no', 'name']);

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('hr.employee-work-policies.create', compact('employees', 'departments'));
    }

    /**
     * Store a newly created work policy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'weekday_start' => 'nullable|date_format:H:i',
            'weekday_end' => 'nullable|date_format:H:i|after:weekday_start',
            'saturday_start' => 'nullable|date_format:H:i',
            'saturday_end' => 'nullable|date_format:H:i|after:saturday_start',
            'sunday_start' => 'nullable|date_format:H:i',
            'sunday_end' => 'nullable|date_format:H:i|after:sunday_start',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        DB::beginTransaction();
        try {
            $policy = EmployeeWorkPolicy::create([
                'uid' => \Str::uuid(),
                'employee_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'weekday_start' => $validated['weekday_start'] ?? '08:00',
                'weekday_end' => $validated['weekday_end'] ?? '17:00',
                'saturday_start' => $validated['saturday_start'] ?? '08:00',
                'saturday_end' => $validated['saturday_end'] ?? '13:00',
                'sunday_start' => $validated['sunday_start'] ?? null,
                'sunday_end' => $validated['sunday_end'] ?? null,
            ]);

            DB::commit();
            return redirect()->route('employee-work-policies.index')
                ->with('success', "Work policy for {$employee->name} created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create work policy: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified work policy.
     */
    public function edit(EmployeeWorkPolicy $policy)
    {
        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'employee_no', 'name']);
            
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('hr.employee-work-policies.edit', compact('policy', 'employees', 'departments'));
    }

    /**
     * Update the specified work policy.
     */
    public function update(Request $request, EmployeeWorkPolicy $policy)
    {
        $validated = $request->validate([
            'weekday_start' => 'nullable|date_format:H:i',
            'weekday_end' => 'nullable|date_format:H:i|after:weekday_start',
            'saturday_start' => 'nullable|date_format:H:i',
            'saturday_end' => 'nullable|date_format:H:i|after:saturday_start',
            'sunday_start' => 'nullable|date_format:H:i',
            'sunday_end' => 'nullable|date_format:H:i|after:sunday_start',
        ]);

        DB::beginTransaction();
        try {
            $policy->update([
                'weekday_start' => $validated['weekday_start'] ?? $policy->weekday_start,
                'weekday_end' => $validated['weekday_end'] ?? $policy->weekday_end,
                'saturday_start' => $validated['saturday_start'] ?? $policy->saturday_start,
                'saturday_end' => $validated['saturday_end'] ?? $policy->saturday_end,
                'sunday_start' => $validated['sunday_start'] ?? $policy->sunday_start,
                'sunday_end' => $validated['sunday_end'] ?? $policy->sunday_end,
            ]);

            DB::commit();
            return redirect()->route('employee-work-policies.index')
                ->with('success', "Work policy updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update work policy: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified work policy.
     */
    public function destroy(EmployeeWorkPolicy $policy)
    {
        $employeeName = $policy->employee->name ?? 'Unknown';
        $policy->delete();
        return redirect()->route('employee-work-policies.index')
            ->with('success', "Work policy for {$employeeName} deleted successfully.");
    }

    /**
     * Import work policies from Excel file.
     */
    public function storeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            $import = new WorkPoliciesImport();
            Excel::import($import, $request->file('file'));

            $result = $import->getResults();
            
            $message = "Import selesai: {$result['success']} baru, {$result['updated']} diperbarui, {$result['failed']} gagal.";
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'results' => $result
                ]);
            }

            return redirect()->route('employee-work-policies.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('employee-work-policies.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint untuk mendapatkan jam kerja karyawan.
     */
    public function getHours(Employee $employee)
    {
        $policy = $employee->workPolicy;
        
        if (!$policy) {
            return response()->json([
                'exists' => false,
                'message' => 'No work policy found for this employee.'
            ]);
        }

        return response()->json([
            'exists' => true,
            'weekday_hours' => $policy->weekday_hours,
            'saturday_hours' => $policy->saturday_hours,
            'sunday_hours' => $policy->sunday_hours,
            'weekday_start' => $policy->weekday_start?->format('H:i'),
            'weekday_end' => $policy->weekday_end?->format('H:i'),
            'saturday_start' => $policy->saturday_start?->format('H:i'),
            'saturday_end' => $policy->saturday_end?->format('H:i'),
            'sunday_start' => $policy->sunday_start?->format('H:i'),
            'sunday_end' => $policy->sunday_end?->format('H:i'),
        ]);
    }
}