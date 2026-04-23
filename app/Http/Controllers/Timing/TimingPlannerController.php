<?php

namespace App\Http\Controllers\Timing;

use App\Http\Controllers\Controller;
use App\Models\Production\JobOrder;
use App\Models\Production\JobOrderTimingPlan;
use App\Models\Production\Timing;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimingPlannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function authorizeAccess()
    {
        $user = auth()->user();
        if (!in_array($user->role, ['super_admin', 'admin_mascot', 'admin_costume'])) {
            abort(403, 'Hanya admin mascot / admin costume yang bisa akses Timing Planner.');
        }
    }

    /**
     * List all current plans grouped by Job Order.
     */
    public function index()
    {
        $this->authorizeAccess();

        // Get relevant departments
        $sharedDepts = Department::where(function ($q) {
            $q->where('name', 'LIKE', '%mascot%')->orWhere('name', 'LIKE', '%animatronic%')->orWhere('name', 'LIKE', '%animation%')->orWhere('name', 'LIKE', '%costume%');
        })
            ->pluck('id')
            ->toArray();

        // Active JOs (not Delivered)
        $jobOrders = JobOrder::with(['project', 'department'])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Delivered');
            })
            ->where(function ($q) use ($sharedDepts) {
                $q->whereIn('department_id', $sharedDepts)->orWhereHas('departments', function ($dq) use ($sharedDepts) {
                    $dq->whereIn('departments.id', $sharedDepts);
                });
            })
            ->orderByRaw('CASE WHEN delivery_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('delivery_date', 'asc')
            ->get();

        // Load existing plans keyed by job_order_id
        $joIds = $jobOrders->pluck('id')->toArray();
        $plans = JobOrderTimingPlan::with(['employee', 'createdBy'])
            ->whereIn('job_order_id', $joIds)
            ->get()
            ->groupBy('job_order_id');

        // Available employees (active mascot + costume dept employees)
        $employees = Employee::where('status', 'active')
            ->whereIn('department_id', $sharedDepts)
            ->with(['department', 'skillsets'])
            ->orderBy('name')
            ->get();

        // Last completed stage per JO (from timings.department_specific_data->current_stage)
        $lastStages = Timing::whereIn('job_order_id', $joIds)->whereNotNull('department_specific_data')->orderByDesc('updated_at')->get()->groupBy('job_order_id')->map(fn($group) => $group->first()->department_specific_data['current_stage'] ?? 0)->toArray();

        return view('timing.planner.index', compact('jobOrders', 'plans', 'employees', 'lastStages'));
    }

    /**
     * Save (replace) the plan for a specific Job Order.
     * Replaces the entire employee set for that JO.
     */
    public function savePlan(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'job_order_id' => 'required|exists:job_orders,id',
            'rows' => 'required|array|min:1',
            'rows.*.employee_id' => 'required|exists:employees,id',
            'rows.*.task' => 'required|string|max:255',
            'rows.*.stage' => 'required|string|max:100',
            'rows.*.session_type' => 'required|in:mass_production,repair',
        ]);

        $joId = $request->job_order_id;
        $userId = auth()->id();

        // Deduplicate by employee_id (keep last)
        $rowsMap = [];
        foreach ($request->rows as $row) {
            $rowsMap[$row['employee_id']] = $row;
        }

        DB::beginTransaction();
        try {
            JobOrderTimingPlan::where('job_order_id', $joId)->delete();

            $rows = [];
            foreach ($rowsMap as $empId => $row) {
                $rows[] = [
                    'job_order_id' => $joId,
                    'employee_id' => $empId,
                    'task' => $row['task'] ?? null,
                    'stage' => $row['stage'] ?? null,
                    'session_type' => $row['session_type'] ?? 'mass_production',
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            JobOrderTimingPlan::insert($rows);

            DB::commit();

            $empIds = array_keys($rowsMap);
            $empNames = Employee::whereIn('id', $empIds)->pluck('name')->implode(', ');
            return response()->json([
                'success' => true,
                'message' => 'Plan disimpan untuk ' . count($empIds) . ' karyawan.',
                'employee_ids' => $empIds,
                'employee_names' => $empNames,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear the plan for a specific Job Order.
     */
    public function clearPlan(Request $request)
    {
        $this->authorizeAccess();

        $request->validate(['job_order_id' => 'required|exists:job_orders,id']);

        JobOrderTimingPlan::where('job_order_id', $request->job_order_id)->delete();

        return response()->json(['success' => true, 'message' => 'Plan berhasil dihapus.']);
    }

    /**
     * Get planned employees for a specific Job Order (AJAX — used by Mascot Timing page).
     */
    public function getPlan(string $jobOrderId)
    {
        $this->authorizeAccess();

        $plans = JobOrderTimingPlan::with('employee:id,name,photo,position,department_id')->where('job_order_id', $jobOrderId)->get();

        return response()->json([
            'success' => true,
            'employee_ids' => $plans->pluck('employee_id')->toArray(),
            'employees' => $plans->map(
                fn($p) => [
                    'id' => $p->employee_id,
                    'name' => $p->employee->name ?? 'N/A',
                    'position' => $p->employee->position ?? '',
                    'task' => $p->task ?? '',
                    'stage' => $p->stage ?? '',
                    'session_type' => $p->session_type ?? 'mass_production',
                ],
            ),
            'updated_at' => $plans->max('updated_at')?->format('d M Y H:i') ?? null,
            'planned_by' => $plans->first()?->createdBy?->username ?? null,
        ]);
    }
}
