<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Admin\Department;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    public function __construct()
    {
        // No middleware - allow guest access
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if user is authenticated
        $isAuthenticated = Auth::check();
        $userRole = $isAuthenticated ? Auth::user()->role : null;

        // If AJAX request, return DataTables data
        if ($request->ajax()) {
            return $this->getDataTablesData($request, $isAuthenticated, $userRole);
        }

        // For non-AJAX requests, return view with master data for filters
        $employees = Employee::with('department')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $leaveTypes = LeaveRequest::getTypeEnumOptions();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        return view('hr.leave_requests.index', compact('employees', 'departments', 'leaveTypes', 'leaveTypeLabels', 'isAuthenticated', 'userRole'));
    }

    private function getDataTablesData(Request $request, $isAuthenticated, $userRole)
    {
        $query = LeaveRequest::with(['employee.department'])->latest();

        // Apply filters
        if ($request->filled('employee_filter')) {
            $query->where('employee_id', $request->employee_filter);
        }

        if ($request->filled('department_filter')) {
            $query->whereHas('employee.department', function ($q) use ($request) {
                $q->where('id', $request->department_filter);
            });
        }

        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }

        if ($request->filled('approval_status_filter')) {
            $status = $request->approval_status_filter;
            if ($status === 'both_approved') {
                $query->where('approval_1', 'approved')->where('approval_2', 'approved');
            } elseif ($status === 'pending') {
                $query->where(function ($q) {
                    $q->where('approval_1', 'pending')->orWhere('approval_2', 'pending');
                });
            } elseif ($status === 'rejected') {
                $query->where(function ($q) {
                    $q->where('approval_1', 'rejected')->orWhere('approval_2', 'rejected');
                });
            }
        }

        if ($request->filled('submitted_at_filter')) {
            $query->whereDate('created_at', $request->submitted_at_filter);
        }

        // Custom search functionality
        if ($request->filled('custom_search')) {
            $searchValue = $request->input('custom_search');
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('employee', function ($sq) use ($searchValue) {
                    $sq->where('name', 'like', "%{$searchValue}%")->orWhere('position', 'like', "%{$searchValue}%");
                })
                    ->orWhere('type', 'like', "%{$searchValue}%")
                    ->orWhere('reason', 'like', "%{$searchValue}%")
                    ->orWhere('duration', 'like', "%{$searchValue}%");
            });
        }

        // DataTables search
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('employee', function ($sq) use ($searchValue) {
                    $sq->where('name', 'like', "%{$searchValue}%")->orWhere('position', 'like', "%{$searchValue}%");
                })
                    ->orWhere('type', 'like', "%{$searchValue}%")
                    ->orWhere('reason', 'like', "%{$searchValue}%");
            });
        }

        // Sorting
        $columns = ['id', 'employee_id', 'start_date', 'end_date', 'duration', 'type', 'approval_1', 'approval_2', 'created_at'];

        if ($request->filled('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'desc');

            if ($orderColumnIndex == 1) {
                // Sort by employee name
                $query->join('employees', 'leave_requests.employee_id', '=', 'employees.id')->orderBy('employees.name', $orderDirection)->select('leave_requests.*');
            } elseif (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get total and filtered counts
        $totalRecords = LeaveRequest::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $leaveRequests = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        foreach ($leaveRequests as $index => $leave) {
            $row = [
                'DT_RowIndex' => $start + $index + 1,
                'employee_name' => $leave->employee->name ?? '-',
                'department' => $leave->employee->department->name ?? '-',
                'position' => $leave->employee->position ?? '-',
                'start_date' => $leave->start_date ? $leave->start_date->format('d M Y') : '-',
                'end_date' => $leave->end_date ? $leave->end_date->format('d M Y') : '-',
                'duration' => rtrim(rtrim(number_format($leave->duration, 2, '.', ''), '0'), '.') . ' days',
                'type' => $leaveTypeLabels[strtoupper($leave->type)] ?? $leave->type,
                'reason' => $leave->reason ?? '-',
                'approval_1' => $this->formatApproval1($leave, $isAuthenticated, $userRole),
                'approval_2' => $this->formatApproval2($leave, $isAuthenticated, $userRole),
                'submitted_on' => '<span data-bs-toggle="tooltip" data-bs-placement="right" title="' . $leave->created_at->format('H:i') . '">' . $leave->created_at->format('d M Y') . '</span>',
            ];

            // Add actions column only for authenticated admin
            if ($isAuthenticated && in_array($userRole, ['super_admin', 'admin_hr'])) {
                $row['actions'] = $this->getActionButtons($leave);
            }

            $data[] = $row;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function formatApproval1($leave, $isAuthenticated, $userRole)
    {
        $badgeClass = $leave->approval_1 == 'approved' ? 'success' : ($leave->approval_1 == 'rejected' ? 'danger' : 'warning text-dark');
        $badge = '<span class="badge bg-' . $badgeClass . '">' . ucfirst($leave->approval_1) . '</span>';

        // Show form only for Admin HR and Super Admin
        if ($isAuthenticated && in_array($userRole, ['admin_hr', 'super_admin'])) {
            return '
            <select name="approval_1"
                    class="form-select form-select-sm d-inline w-auto approval-1-select"
                    data-id="' .
                $leave->id .
                '"
                    data-old-status="' .
                $leave->approval_1 .
                '"
                    data-employee="' .
                ($leave->employee->name ?? 'Unknown') .
                '"
                    data-type="' .
                $leave->type .
                '"
                    data-approval2="' .
                $leave->approval_2 .
                '">
                <option value="pending" ' .
                ($leave->approval_1 == 'pending' ? 'selected' : '') .
                '>Pending</option>
                <option value="approved" ' .
                ($leave->approval_1 == 'approved' ? 'selected' : '') .
                '>Approved</option>
                <option value="rejected" ' .
                ($leave->approval_1 == 'rejected' ? 'selected' : '') .
                '>Rejected</option>
            </select>';
        }

        return $badge;
    }

    private function formatApproval2($leave, $isAuthenticated, $userRole)
    {
        $badgeClass = $leave->approval_2 == 'approved' ? 'success' : ($leave->approval_2 == 'rejected' ? 'danger' : 'warning text-dark');
        $badge = '<span class="badge bg-' . $badgeClass . '">' . ucfirst($leave->approval_2) . '</span>';

        // Show form only for Super Admin
        if ($isAuthenticated && $userRole === 'super_admin') {
            $html =
                '
            <select name="approval_2"
                    class="form-select form-select-sm d-inline w-auto approval-2-select"
                    data-id="' .
                $leave->id .
                '"
                    data-old-status="' .
                $leave->approval_2 .
                '"
                    data-employee="' .
                ($leave->employee->name ?? 'Unknown') .
                '"
                    data-type="' .
                $leave->type .
                '"
                    data-approval1="' .
                $leave->approval_1 .
                '">
                <option value="pending" ' .
                ($leave->approval_2 == 'pending' ? 'selected' : '') .
                '>Pending</option>
                <option value="approved" ' .
                ($leave->approval_2 == 'approved' ? 'selected' : '') .
                '>Approved</option>
                <option value="rejected" ' .
                ($leave->approval_2 == 'rejected' ? 'selected' : '') .
                '>Rejected</option>
            </select>';

            return $html;
        }

        return $badge;
    }

    private function getActionButtons($leave)
    {
        return '<div class="d-flex flex-nowrap gap-1">
            <a href="' .
            route('leave_requests.edit', $leave->id) .
            '" class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
            <button type="button" class="btn btn-danger btn-sm btn-delete"
                data-id="' .
            $leave->id .
            '"
                data-employee="' .
            ($leave->employee->name ?? 'Unknown') .
            '"
                data-bs-toggle="tooltip" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </div>';
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::with('department')->orderBy('name')->get();
        $leaveTypes = LeaveRequest::getTypeEnumOptions();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        return view('hr.leave_requests.create', compact('employees', 'leaveTypes', 'leaveTypeLabels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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

                if (bccomp($employee->saldo_cuti, $request->duration, 2) < 0) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->withErrors(['duration' => 'Insufficient leave balance. Employee has ' . number_format($employee->saldo_cuti, 1) . ' days, but requesting ' . number_format($request->duration, 1) . ' days.']);
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
            return redirect()->route('leave_requests.index')->with('success', 'Leave request submitted successfully! Please wait for approval.');
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
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to edit leave requests.');
        }

        $leave = LeaveRequest::findOrFail($id);
        $employees = Employee::with('department')->orderBy('name')->get();
        $leaveTypes = LeaveRequest::getTypeEnumOptions();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        return view('hr.leave_requests.edit', compact('leave', 'employees', 'leaveTypes', 'leaveTypeLabels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized access.');
        }

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
        if (!Auth::check()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            abort(403, 'Unauthorized access.');
        }

        if (Auth::user()->isReadOnlyAdmin()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to approve leave requests.'], 403);
            }
            abort(403, 'You do not have permission to approve leave requests.');
        }

        $userRole = Auth::user()->role;

        // Validation berdasarkan role
        if ($request->has('approval_1') && !in_array($userRole, ['admin_hr', 'super_admin'])) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only HR Admin can update Approval 1.'], 403);
            }
            return back()->with('error', 'Only HR Admin can update Approval 1.');
        }

        if ($request->has('approval_2') && $userRole !== 'super_admin') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only Super Admin can update Approval 2.'], 403);
            }
            return back()->with('error', 'Only Super Admin can update Approval 2.');
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

            // Update approvals based on role
            if ($request->has('approval_1') && in_array($userRole, ['admin_hr', 'super_admin'])) {
                $leave->approval_1 = $request->approval_1;
            }

            if ($request->has('approval_2') && $userRole === 'super_admin') {
                $leave->approval_2 = $request->approval_2;
            }

            $leave->save();

            // Check conditions
            $bothApproved = $leave->approval_1 === 'approved' && $leave->approval_2 === 'approved';
            $wasNotBothApproved = !($previousApproval1 === 'approved' && $previousApproval2 === 'approved');
            $isAnnualLeave = $leave->type === 'ANNUAL';

            $message = 'Approval updated successfully!';
            $balanceInfo = null;

            // Deduct balance if both approved
            if ($bothApproved && $wasNotBothApproved && $isAnnualLeave) {
                $employee = $leave->employee;

                if (bccomp($employee->saldo_cuti, $leave->duration, 2) < 0) {
                    DB::rollBack();
                    $errorMsg = 'Insufficient leave balance. Employee has ' . number_format($employee->saldo_cuti, 1) . ' days, but requesting ' . number_format($leave->duration, 1) . ' days.';

                    if ($request->ajax()) {
                        return response()->json(['success' => false, 'message' => $errorMsg], 422);
                    }
                    return back()->with('error', $errorMsg);
                }

                $oldBalance = $employee->saldo_cuti;
                $employee->saldo_cuti = bcsub($oldBalance, $leave->duration, 2);
                $employee->save();

                \Log::info('Leave Balance Deducted', [
                    'employee_id' => $employee->id,
                    'old_balance' => number_format($oldBalance, 2),
                    'deduction' => number_format($leave->duration, 2),
                    'new_balance' => number_format($employee->saldo_cuti, 2),
                ]);

                $message = 'Leave approved! Balance reduced by ' . number_format($leave->duration, 1) . ' day(s).';
                $balanceInfo = [
                    'deducted' => number_format($leave->duration, 1),
                    'remaining' => number_format($employee->saldo_cuti, 1),
                ];
            }

            // Check if approval was revoked
            $approvalRevoked = (($previousApproval1 === 'approved' && $request->has('approval_1') && $request->approval_1 !== 'approved') || ($previousApproval2 === 'approved' && $request->has('approval_2') && $request->approval_2 !== 'approved')) && $isAnnualLeave;

            if ($approvalRevoked && ($previousApproval1 === 'approved' && $previousApproval2 === 'approved')) {
                $employee = $leave->employee;
                $oldBalance = $employee->saldo_cuti;
                $employee->saldo_cuti = bcadd($oldBalance, $leave->duration, 2);
                $employee->save();

                $message = 'Approval revoked. Balance restored by ' . number_format($leave->duration, 1) . ' day(s).';
                $balanceInfo = [
                    'restored' => number_format($leave->duration, 1),
                    'new_balance' => number_format($employee->saldo_cuti, 1),
                ];
            }

            DB::commit();

            //AJAX Response
            if ($request->ajax()) {
                // Refresh approval badges HTML
                $approval1Html = $this->formatApproval1($leave, true, $userRole);
                $approval2Html = $this->formatApproval2($leave, true, $userRole);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'balanceInfo' => $balanceInfo,
                    'leave' => [
                        'id' => $leave->id,
                        'approval_1' => $leave->approval_1,
                        'approval_2' => $leave->approval_2,
                        'approval_1_html' => $approval1Html,
                        'approval_2_html' => $approval2Html,
                        'is_both_approved' => $bothApproved,
                    ],
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Leave approval error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to update: ' . $e->getMessage()], 500);
            }

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
        if (!Auth::check()) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            abort(403, 'Unauthorized access.');
        }

        if (Auth::user()->isReadOnlyAdmin()) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to delete leave requests.'], 403);
            }
            abort(403, 'You do not have permission to delete leave requests.');
        }

        if (!in_array(Auth::user()->role, ['super_admin', 'admin_hr'])) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only Super Admin and HR Admin can delete leave requests.'], 403);
            }
            return redirect()->route('leave_requests.index')->with('error', 'Only Super Admin and HR Admin can delete leave requests.');
        }

        $leave = LeaveRequest::findOrFail($id);
        $employeeName = $leave->employee->name ?? 'Unknown';
        $leave->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Leave request for <b>{$employeeName}</b> deleted successfully!"]);
        }

        return redirect()
            ->route('leave_requests.index')
            ->with('success', "Leave request for <b>{$employeeName}</b> deleted successfully!");
    }
}
