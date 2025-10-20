<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
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
     * Display a listing of the resource.
     */
    public function index()
    {
        $leaveRequests = LeaveRequest::with(['employee.department'])
            ->latest()
            ->paginate(10);

        $leaveTypeLabels = [
            'ANNUAL' => 'Annual Leave',
            'MATERNITY' => 'Maternity (3 months)',
            'WEDDING' => 'Emp.Self Wedding (3 days)',
            'SONWED' => 'Son/Daughter Wedding (2 days)',
            'BIRTHCHILD' => 'Birth child/Misscarriage (2 days)',
            'UNPAID' => 'Unpaid Leave',
            'DEATH' => 'Death of family member living in the same house (1 day)',
            'DEATH_2' => 'Death of spouse/child or child in law/parent in law (2 days)',
            'BAPTISM' => 'Child Circumcision/Baptism (2 days)',
        ];

        return view('leave_requests.index', compact('leaveRequests', 'leaveTypeLabels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::with('department')->orderBy('name')->get();
        $leaveTypes = LeaveRequest::getTypeEnumOptions();

        // Label leave type (bisa custom, contoh di bawah)
        $leaveTypeLabels = [
            'ANNUAL' => 'Annual Leave',
            'MATERNITY' => 'Maternity (3 months)',
            'WEDDING' => 'Emp.Self Wedding (3 days)',
            'SONWED' => 'Son/Daughter Wedding (2 days)',
            'BIRTHCHILD' => 'Birth child/Misscarriage (2 days)',
            'UNPAID' => 'Unpaid Leave',
            'DEATH' => 'Death of family member living in the same house (1 day)',
            'DEATH_2' => 'Death of spouse/child or child in law/parent in law (2 days)',
            'BAPTISM' => 'Child Circumcision/Baptism (2 days)',
        ];

        return view('leave_requests.create', compact('employees', 'leaveTypes', 'leaveTypeLabels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create leave requests.');
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string',
            'reason' => 'nullable|string',
            'duration' => 'required|numeric|min:0.01|max:999.99',
        ]);

        DB::beginTransaction();
        try {
            // Check leave balance for Annual Leave
            if (strtoupper($request->type) === 'ANNUAL') {
                $employee = Employee::findOrFail($request->employee_id);

                if ($employee->saldo_cuti < $request->duration) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->withErrors(['duration' => "Insufficient leave balance. Employee has {$employee->saldo_cuti} days, but requesting {$request->duration} days."]);
                }
            }

            LeaveRequest::create([
                'employee_id' => $request->employee_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'type' => $request->type,
                'duration' => $request->duration,
                'reason' => $request->reason,
                'approval_1' => 'pending',
                'approval_2' => 'pending',
            ]);

            DB::commit();
            return redirect()->route('leave_requests.index')->with('success', 'Leave request submitted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Leave request creation error: ' . $e->getMessage());
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create leave request: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $employees = Employee::with('department')->orderBy('name')->get();
        $leaveTypes = LeaveRequest::getTypeEnumOptions();
        $leaveTypeLabels = [
            'ANNUAL' => 'Annual Leave',
            'MATERNITY' => 'Maternity (3 months)',
            'WEDDING' => 'Emp.Self Wedding (3 days)',
            'SONWED' => 'Son/Daughter Wedding (2 days)',
            'BIRTHCHILD' => 'Birth child/Misscarriage (2 days)',
            'UNPAID' => 'Unpaid Leave',
            'DEATH' => 'Death of family member living in the same house (1 day)',
            'DEATH_2' => 'Death of spouse/child or child in law/parent in law (2 days)',
            'BAPTISM' => 'Child Circumcision/Baptism (2 days)',
        ];

        return view('leave_requests.edit', compact('leave', 'employees', 'leaveTypes', 'leaveTypeLabels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to update leave requests.');
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string',
            'reason' => 'nullable|string',
            'duration' => 'required|numeric|min:0.01|max:999.99',
        ]);

        $leave = LeaveRequest::findOrFail($id);

        $leave->update([
            'employee_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'type' => $request->type,
            'duration' => $request->duration,
            'reason' => $request->reason,
        ]);

        return redirect()->route('leave_requests.index')->with('success', 'Leave request updated!');
    }

    public function updateApproval(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to approve leave requests.');
        }

        if (!in_array(Auth::user()->role, ['super_admin', 'admin_hr'])) {
            return back()->with('error', 'Only Super Admin and HR Admin can approve leave requests.');
        }

        $request->validate([
            'approval_1' => 'nullable|in:pending,approved,rejected',
            'approval_2' => 'nullable|in:pending,approved,rejected',
        ]);

        DB::beginTransaction();
        try {
            $leave = LeaveRequest::with('employee')->findOrFail($id);

            // Store previous status
            $previousApproval1 = $leave->approval_1;
            $previousApproval2 = $leave->approval_2;

            // Update approvals
            if ($request->has('approval_1')) {
                $leave->approval_1 = $request->approval_1;
            }
            if ($request->has('approval_2')) {
                $leave->approval_2 = $request->approval_2;
            }
            $leave->save();

            // SIMPLIFIED: Direct ENUM comparison
            $bothApproved = $leave->approval_1 === 'approved' && $leave->approval_2 === 'approved';
            $wasNotBothApproved = !($previousApproval1 === 'approved' && $previousApproval2 === 'approved');
            $isAnnualLeave = $leave->type === 'ANNUAL'; // ✅ Simple!

            if ($bothApproved && $wasNotBothApproved && $isAnnualLeave) {
                $employee = $leave->employee;

                // ✅ PENTING: Gunakan bccomp untuk compare decimal dengan tepat
                if (bccomp($employee->saldo_cuti, $leave->duration, 2) < 0) {
                    DB::rollBack();
                    return back()->with('error', 'Insufficient leave balance. Employee has ' . number_format($employee->saldo_cuti, 1) . ' days, but requesting ' . number_format($leave->duration, 1) . ' days.');
                }

                $oldBalance = $employee->saldo_cuti;

                // ✅ Gunakan bcsub untuk pengurangan decimal yang akurat
                $employee->saldo_cuti = bcsub($oldBalance, $leave->duration, 2);
                $employee->save();

                \Log::info('Leave Balance Deducted', [
                    'employee_id' => $employee->id,
                    'old_balance' => number_format($oldBalance, 2),
                    'deduction' => number_format($leave->duration, 2),
                    'new_balance' => number_format($employee->saldo_cuti, 2),
                ]);

                DB::commit();
                return back()->with('success', 'Leave approved! Balance reduced by ' . number_format($leave->duration, 1) . ' day(s). Remaining: ' . number_format($employee->saldo_cuti, 1) . ' day(s).');
            }

            // Restore balance jika revoked
            if ($approvalRevoked && ($previousApproval1 === 'approved' && $previousApproval2 === 'approved')) {
                $employee = $leave->employee;
                $oldBalance = $employee->saldo_cuti;

                // ✅ Gunakan bcadd untuk penambahan decimal yang akurat
                $employee->saldo_cuti = bcadd($oldBalance, $leave->duration, 2);
                $employee->save();

                DB::commit();
                return back()->with('success', 'Approval revoked. Balance restored by ' . number_format($leave->duration, 1) . ' day(s).');
            }

            DB::commit();
            return back()->with('success', 'Approval updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Leave approval error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to delete leave requests.');
        }

        // Hanya super_admin dan admin_hr yang bisa delete
        if (!in_array(Auth::user()->role, ['super_admin', 'admin_hr'])) {
            return redirect()->route('leave_requests.index')->with('error', 'Only Super Admin and HR Admin can delete leave requests.');
        }

        $leave = LeaveRequest::findOrFail($id);
        $leave->delete();
        return redirect()->route('leave_requests.index')->with('success', 'Leave request deleted!');
    }
}
