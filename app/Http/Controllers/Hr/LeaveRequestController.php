<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Admin\Department;
use Illuminate\Support\Facades\DB;
use App\Models\Hr\ApprovalMatrix;
use App\Models\Hr\ApprovalTransaction;
use App\Models\Hr\DailyAttendance;
use App\Services\ApprovalService;
use App\Services\DailyAttendanceService;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    public function __construct(private ApprovalService $approvalService)
    {
        $this->middleware('auth')->except(['create', 'store']);
        $this->middleware('can:hr.leave.view')->except(['create', 'store']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isAuthenticated = Auth::check();
        $userRole        = $isAuthenticated ? Auth::user()->role : null;

        $query = LeaveRequest::with(['employee.department'])->latest();

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee.department', fn($q) => $q->where('id', $request->department_id));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('approval_status')) {
            match ($request->approval_status) {
                'both_approved' => $query->where('approval_1', 'approved')->where('approval_2', 'approved'),
                'pending'       => $query->where(fn($q) => $q->where('approval_1', 'pending')->orWhere('approval_2', 'pending')),
                'rejected'      => $query->where(fn($q) => $q->where('approval_1', 'rejected')->orWhere('approval_2', 'rejected')),
                default         => null,
            };
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->whereHas('employee', fn($sq) => $sq->where('name', 'like', "%$s%")->orWhere('position', 'like', "%$s%"))
                ->orWhere('type', 'like', "%$s%")
                ->orWhere('reason', 'like', "%$s%")
            );
        }

        $leaves          = $query->paginate(15)->withQueryString();
        $employees       = Employee::with('department')->orderBy('name')->get();
        $departments     = Department::orderBy('name')->get();
        $leaveTypes      = LeaveRequest::getTypeEnumOptions();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        return view('hr.leave_requests.index', compact(
            'leaves', 'employees', 'departments', 'leaveTypes', 'leaveTypeLabels', 'isAuthenticated', 'userRole'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::with('department')
            ->active()
            ->select(['id', 'name', 'position', 'department_id', 'hire_date', 'saldo_cuti', 'status', 'menstruation_leave_approved'])
            ->orderBy('name')
            ->get();
        $leaveTypes = LeaveRequest::getTypeEnumOptions();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        return view('hr.leave_requests.create', compact('employees', 'leaveTypes', 'leaveTypeLabels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
// Validate employee is active
        $employee = Employee::with('department')->findOrFail($request->employee_id);

        if (!$employee->isActive()) {
            return back()
                ->withInput()
                ->withErrors(['employee_id' => "Cannot create leave request for {$employee->name}. Employee status is {$employee->status}."]);
        }

        $leaveTypeInput = strtoupper($request->type ?? '');

        // Build dynamic validation rules for file uploads
        $fileRules = [];
        if ($leaveTypeInput === 'SICK') {
            $fileRules['mc_document'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }
        if ($leaveTypeInput === 'MENSTRUATION') {
            $fileRules['doctor_letter'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        $request->validate(array_merge([
            'employee_id'     => 'required|exists:employees,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'type'            => 'required|string',
            'reason'          => 'nullable|string',
            'duration'        => 'required|numeric|min:0.01|max:999.99',
            'leave_time_from' => 'nullable|date_format:H:i',
            'leave_time_to'   => ($leaveTypeInput === 'PERMISSION_OUT' ? 'required' : 'nullable') . '|date_format:H:i|after:leave_time_from',
        ], $fileRules));

        // Auto-calculate end_date for fixed-day leave types
        $fixedDayTypes = [
            'EMP_SELF_WEDDING' => 3,
            'BIRTH_CHILD_MISCARRIAGE' => 2,
            'DEATH_FAMILY_SAME_HOUSE' => 1,
            'CHILD_CIRCUMCISION_BAPTISM' => 2,
            'SON_DAUGHTER_WEDDING' => 2,
            'DEATH_SPOUSE_CHILD_PARENT_IN_LAW' => 2,
        ];

        $leaveType = strtoupper($request->type);

        if (isset($fixedDayTypes[$leaveType])) {
            // Auto-calculate end_date based on start_date + fixed duration
            $startDate = new \DateTime($request->start_date);
            $duration = $fixedDayTypes[$leaveType];
            $endDate = clone $startDate;
            $endDate->modify('+' . ($duration - 1) . ' days');

            // Override end_date and duration from request
            $request->merge([
                'end_date' => $endDate->format('Y-m-d'),
                'duration' => $duration,
            ]);
        }

        DB::beginTransaction();
        try {
            // Check leave balance for Annual Leave BEFORE creating
            if (strtoupper($request->type) === 'ANNUAL') {
                if (bccomp($employee->saldo_cuti, $request->duration, 2) < 0) {
                    DB::rollBack();

                    $message = 'Maaf, saldo cuti tidak mencukupi. Saldo tersedia: ' . number_format($employee->saldo_cuti, 1) . ' hari, permintaan: ' . number_format($request->duration, 1) . ' hari.';

                    if (!Auth::check()) {
                        // For guest, show SweetAlert
                        return back()->withInput()->with('error_alert', $message);
                    }

                    return back()
                        ->withInput()
                        ->withErrors(['duration' => $message]);
                }
            }

            // Handle file uploads — stored as base64 JSON in DB
            $mcDocumentData    = null;
            $doctorLetterData  = null;

            if ($leaveTypeInput === 'SICK' && $request->hasFile('mc_document')) {
                $file = $request->file('mc_document');
                $mcDocumentData = json_encode([
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'data' => base64_encode(file_get_contents($file->getRealPath())),
                ]);
            }

            if ($leaveTypeInput === 'MENSTRUATION' && $request->hasFile('doctor_letter')) {
                $file = $request->file('doctor_letter');
                $doctorLetterData = json_encode([
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'data' => base64_encode(file_get_contents($file->getRealPath())),
                ]);
            }

            // Skip dept approval jika tipe tertentu (SICK, MENSTRUATION, dll) ATAU dept tidak ada di approval map
            $deptInApprovalList = in_array(
                optional($employee->department)->name,
                \App\Models\Hr\LeaveRequest::getDeptApprovalDepartments()
            );
            $skipsDept    = !$deptInApprovalList || in_array(strtoupper($request->type), \App\Models\Hr\LeaveRequest::SKIP_DEPT_APPROVAL_TYPES);
            $approvalDept = $skipsDept ? 'approved' : 'pending';

            $leave = LeaveRequest::create([
                'employee_id'     => $request->employee_id,
                'start_date'      => $request->start_date,
                'end_date'        => $request->end_date,
                'leave_time_from' => in_array($leaveTypeInput, ['EARLY_LEAVE', 'PERMISSION_OUT']) ? $request->leave_time_from : null,
                'leave_time_to'   => in_array($leaveTypeInput, ['EARLY_LEAVE', 'PERMISSION_OUT']) ? $request->leave_time_to : null,
                'type'            => $request->type,
                'duration'        => $request->duration,
                'reason'          => $request->reason,
                'mc_document'     => $mcDocumentData,
                'doctor_letter'   => $doctorLetterData,
                'approval_dept'   => $approvalDept,
                'approval_1'      => 'pending',
                'approval_2'      => 'pending',
            ]);

            // Inisiasi audit trail di approval_transactions (jika matrix sudah dikonfigurasi)
            if (ApprovalMatrix::where('module', 'leave')->exists()) {
                $this->approvalService->initiate('leave', $leave->id);
            }

            DB::commit();

            // Different response for authenticated vs guest users
            if (Auth::check()) {
                return redirect()->route('leave_requests.index')->with('success', 'Leave request submitted successfully! Please wait for approval.');
            } else {
                // For guest users, return with success flag for JS to handle
                return back()->with('guest_success', 'Your leave request has been submitted successfully and is being processed. Thank you!');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Leave request creation error: ' . $e->getMessage());
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create leave request: ' . $e->getMessage()]);
        }
    } /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveRequest $leave)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to edit leave requests.');
        }

        $employees = Employee::with('department')
            ->active()
            ->select(['id', 'name', 'position', 'department_id', 'hire_date', 'saldo_cuti', 'status', 'menstruation_leave_approved'])
            ->orderBy('name')
            ->get();

        $leaveTypes = LeaveRequest::getTypeEnumOptions();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        return view('hr.leave_requests.edit', compact('leave', 'employees', 'leaveTypes', 'leaveTypeLabels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leave)
    {
        // Validate employee is active
        $employee = Employee::with('department')->findOrFail($request->employee_id);

        if (!$employee->isActive()) {
            return back()
                ->withInput()
                ->withErrors(['employee_id' => "Cannot update leave request for {$employee->name}. Employee status is {$employee->status}."]);
        }

        $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'type'            => 'required|string',
            'reason'          => 'nullable|string',
            'duration'        => 'required|numeric|min:0.01|max:999.99',
            'leave_time_from' => 'nullable|date_format:H:i',
            'leave_time_to'   => 'nullable|date_format:H:i|after:leave_time_from',
        ]);

        // Recalculate approval_dept if type changed
        $deptInApprovalList = in_array(
            optional($employee->department)->name,
            \App\Models\Hr\LeaveRequest::getDeptApprovalDepartments()
        );
        $skipsDept    = !$deptInApprovalList || in_array(strtoupper($request->type), \App\Models\Hr\LeaveRequest::SKIP_DEPT_APPROVAL_TYPES);
        $approvalDept = $skipsDept ? 'approved' : ($leave->approval_dept ?? 'pending');

        $leave->update([
            'employee_id'     => $request->employee_id,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'leave_time_from' => in_array(strtoupper($request->type), ['EARLY_LEAVE', 'PERMISSION_OUT']) ? $request->leave_time_from : null,
            'leave_time_to'   => in_array(strtoupper($request->type), ['EARLY_LEAVE', 'PERMISSION_OUT']) ? $request->leave_time_to : null,
            'type'            => $request->type,
            'duration'        => $request->duration,
            'reason'          => $request->reason,
            'approval_dept'   => $approvalDept,
        ]);

        return redirect()->route('leave_requests.index')->with('success', 'Leave request updated!');
    }

    public function updateApproval(Request $request, LeaveRequest $leave)
    {
        if (!Auth::check()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            abort(403, 'Unauthorized access.');
        }

        $userRole = Auth::user()->role;

        // Ambil semua role yang diizinkan dari approval_matrix
        $levelDeptMatrix    = ApprovalMatrix::where('module', 'leave')->where('level', 1)->first();
        $level1Matrix       = ApprovalMatrix::where('module', 'leave')->where('level', 2)->first();
        $level2Matrix       = ApprovalMatrix::where('module', 'leave')->where('level', 3)->first();
        $deptAllowedRoles   = $levelDeptMatrix ? $levelDeptMatrix->getAllowedRoles() : array_keys(LeaveRequest::DEPT_ROLE_MAP);
        $level1AllowedRoles = $level1Matrix ? $level1Matrix->getAllowedRoles() : ['admin_hr'];
        $level2AllowedRoles = $level2Matrix ? $level2Matrix->getAllowedRoles() : ['director', 'admin_hr'];

        if ($request->has('approval_dept') && !in_array($userRole, $deptAllowedRoles) && $userRole !== 'super_admin') {
            $msg = 'Anda tidak memiliki permission untuk Dept Approval.';
            return $request->ajax() ? response()->json(['success' => false, 'message' => $msg], 403) : back()->with('error', $msg);
        }

        if ($request->has('approval_1') && !in_array($userRole, $level1AllowedRoles) && $userRole !== 'super_admin') {
            $label = implode(' / ', $level1AllowedRoles);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => "Hanya role [{$label}] yang dapat mengubah Approval HR."], 403);
            }
            return back()->with('error', "Hanya role [{$label}] yang dapat mengubah Approval HR.");
        }

        if ($request->has('approval_2') && !in_array($userRole, $level2AllowedRoles) && $userRole !== 'super_admin') {
            $label = implode(' / ', $level2AllowedRoles);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => "Hanya role [{$label}] yang dapat mengubah Approval Director."], 403);
            }
            return back()->with('error', "Hanya role [{$label}] yang dapat mengubah Approval Director.");
        }

        $request->validate([
            'approval_dept' => 'nullable|in:pending,approved,rejected',
            'approval_1'    => 'nullable|in:pending,approved,rejected',
            'approval_2'    => 'nullable|in:pending,approved,rejected',
        ]);

        DB::beginTransaction();
        try {
            $leave->load('employee');

            // Store previous status
            $previousApprovalDept = $leave->approval_dept;
            $previousApproval1    = $leave->approval_1;
            $previousApproval2    = $leave->approval_2;

            // Update approvals — dengan validasi urutan
            if ($request->has('approval_dept') && (in_array($userRole, $deptAllowedRoles) || in_array($userRole, ['super_admin', 'admin']))) {
                $leave->approval_dept = $request->approval_dept;
            }

            if ($request->has('approval_1') && (in_array($userRole, $level1AllowedRoles) || $userRole === 'super_admin')) {
                // HR hanya bisa approve jika dept sudah approved
                if ($request->approval_1 === 'approved' && $leave->approval_dept !== 'approved') {
                    DB::rollBack();
                    $msg = 'Tidak bisa approve: Production/Dept approval belum selesai.';
                    return $request->ajax()
                        ? response()->json(['success' => false, 'message' => $msg], 422)
                        : back()->with('error', $msg);
                }
                $leave->approval_1 = $request->approval_1;
            }

            if ($request->has('approval_2') && (in_array($userRole, $level2AllowedRoles) || $userRole === 'super_admin')) {
                // Director hanya bisa approve jika HR sudah approved
                if ($request->approval_2 === 'approved' && $leave->approval_1 !== 'approved') {
                    DB::rollBack();
                    $msg = 'Tidak bisa approve: HR approval belum selesai.';
                    return $request->ajax()
                        ? response()->json(['success' => false, 'message' => $msg], 422)
                        : back()->with('error', $msg);
                }
                $leave->approval_2 = $request->approval_2;
            }

            $leave->save();

            // Audit trail
            if ($request->has('approval_dept') && (in_array($userRole, $deptAllowedRoles) || $userRole === 'super_admin')) {
                ApprovalTransaction::create([
                    'module' => 'leave', 'reference_id' => $leave->id, 'level' => 1,
                    'approved_by' => auth()->id(), 'status' => $leave->approval_dept,
                    'approved_at' => $leave->approval_dept !== 'pending' ? now() : null,
                ]);
            }
            if ($request->has('approval_1') && (in_array($userRole, $level1AllowedRoles) || $userRole === 'super_admin')) {
                ApprovalTransaction::create([
                    'module' => 'leave', 'reference_id' => $leave->id, 'level' => 2,
                    'approved_by' => auth()->id(), 'status' => $leave->approval_1,
                    'approved_at' => $leave->approval_1 !== 'pending' ? now() : null,
                ]);
            }
            if ($request->has('approval_2') && (in_array($userRole, $level2AllowedRoles) || $userRole === 'super_admin')) {
                ApprovalTransaction::create([
                    'module' => 'leave', 'reference_id' => $leave->id, 'level' => 3,
                    'approved_by' => auth()->id(), 'status' => $leave->approval_2,
                    'approved_at' => $leave->approval_2 !== 'pending' ? now() : null,
                ]);
            }

            // Check conditions
            $bothApproved       = $leave->isFullyApproved();
            $wasNotBothApproved = !($previousApprovalDept === 'approved' && $previousApproval1 === 'approved' && $previousApproval2 === 'approved');
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
            $wasFullyApproved   = $previousApprovalDept === 'approved' && $previousApproval1 === 'approved' && $previousApproval2 === 'approved';
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

            // Sync daily_attendances status berdasarkan approval leave
            if ($bothApproved && $wasNotBothApproved) {
                // Leave baru saja fully approved → update daily attendance
                $this->syncDailyAttendanceForLeave($leave, 'approved');
            } elseif ($wasFullyApproved && !$leave->isFullyApproved()) {
                // Leave sebelumnya fully approved, sekarang di-revoke → kembalikan ke Alpha
                $this->syncDailyAttendanceForLeave($leave, 'revoked');
            }

            DB::commit();

            //AJAX Response
            if ($request->ajax()) {
                // Refresh approval badges HTML
                $approval1Html = $this->formatApproval1($leave, true, $userRole);
                $approval2Html = $this->formatApproval2($leave, true, $userRole, $level2AllowedRoles);

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
     * Dept Leave Approvals page (approval_dept - Level 1)
     */
    public function deptLeaveApprovals(Request $request)
    {
        $userRole    = Auth::user()->role;
        $deptRoleMap = LeaveRequest::DEPT_ROLE_MAP;
        $deptMatrix   = ApprovalMatrix::where('module', 'leave')->where('level', 1)->first();
        $allowedRoles = $deptMatrix ? $deptMatrix->getAllowedRoles() : array_keys($deptRoleMap);
        // super_admin and admin can see all departments
        $allDeptRoles = array_merge($allowedRoles, ['super_admin', 'admin']);

        if (!in_array($userRole, $allDeptRoles)) {
            abort(403, 'You do not have permission to access Dept Leave Approvals.');
        }

        // Per-group pending counts (multi-dept roles are combined into one entry)
        $deptPendingCounts = [];
        $combinedDeptMap   = []; // "Label" => [dept array] for reverse-lookup
        foreach ($deptRoleMap as $role => $depts) {
            $deptsList = (array) $depts;
            $groupKey  = implode(' & ', $deptsList); // "DCM Costume & DCM Plush" for multi-dept
            $deptPendingCounts[$groupKey] = LeaveRequest::where('approval_dept', 'pending')
                ->whereHas('employee.department', fn($q) => $q->whereIn('name', $deptsList))
                ->count();
            if (count($deptsList) > 1) {
                $combinedDeptMap[$groupKey] = $deptsList;
            }
        }


        // super_admin / admin without ?dept → show department card index
        $isAllAccess = in_array($userRole, ['super_admin', 'admin']);
        if ($isAllAccess && !$request->filled('dept')) {
            return view('hr.leave_requests.dept-approvals-index', compact('deptPendingCounts'));
        }

        // Dept admin → filter by all their depts; super_admin/admin with ?dept → filter by that dept
        if (!$isAllAccess && isset($deptRoleMap[$userRole])) {
            $filterDepts = (array) $deptRoleMap[$userRole];
            $deptName    = implode(' & ', $filterDepts);
        } else {
            $filterDepts = isset($combinedDeptMap[$request->dept])
                ? $combinedDeptMap[$request->dept]
                : [$request->dept];
            $deptName    = $request->dept;
        }

        $query = LeaveRequest::with(['employee.department'])
            ->where('approval_dept', 'pending')
            ->latest();

        $query->whereHas('employee.department', fn($q) => $q->whereIn('name', $filterDepts));

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        $leaves          = $query->paginate(15)->withQueryString();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        return view('hr.leave_requests.dept-approvals', compact(
            'leaves', 'leaveTypeLabels', 'deptName', 'deptPendingCounts'
        ));
    }

    /**
     * HR Leave Approvals page (approval_1 — Level 2)
     */
    public function hrLeaveApprovals(Request $request)
    {
        $level1Matrix = ApprovalMatrix::where('module', 'leave')->where('level', 2)->first();
        $level1AllowedRoles = $level1Matrix ? $level1Matrix->getAllowedRoles() : ['admin_hr'];
        $level1AllowedRoles[] = 'super_admin';

        if (!Auth::user()->can('hr.leave.view')) {
            abort(403, 'You do not have permission to access HR Leave Approvals.');
        }

        $query = LeaveRequest::with(['employee.department'])
            ->where('approval_1', 'pending')
            ->where('approval_dept', 'approved')
            ->latest();

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        $leaves = $query->paginate(15)->withQueryString();
        $employees = Employee::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        $stats = [
            'total_pending' => LeaveRequest::where('approval_1', 'pending')->where('approval_dept', 'approved')->count(),
            'this_month'    => LeaveRequest::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count(),
            'total_days'    => LeaveRequest::where('approval_1', 'pending')->where('approval_dept', 'approved')->sum('duration'),
            'avg_days'      => 0,
        ];
        $directorPendingCount = LeaveRequest::where('approval_2', 'pending')->where('approval_1', 'approved')->count();

        return view('hr.leave_requests.hr-approvals', compact(
            'leaves', 'employees', 'departments', 'leaveTypeLabels', 'stats', 'directorPendingCount'
        ));
    }

    /**
     * Director Leave Approvals page (approval_2 — Level 3)
     */
    public function directorLeaveApprovals(Request $request)
    {
        $level2Matrix = ApprovalMatrix::where('module', 'leave')->where('level', 3)->first();
        $level2AllowedRoles = $level2Matrix ? $level2Matrix->getAllowedRoles() : ['director', 'admin_hr'];
        $level2AllowedRoles[] = 'super_admin';

        if (!Auth::user()->can('hr.leave.view')) {
            abort(403, 'You do not have permission to access Director Leave Approvals.');
        }

        $query = LeaveRequest::with(['employee.department'])
            ->where('approval_2', 'pending')
            ->where('approval_1', 'approved')
            ->latest();

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        $leaves = $query->paginate(15)->withQueryString();
        $employees = Employee::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get();
        $leaveTypeLabels = LeaveRequest::getTypeLabels();

        $stats = [
            'total_pending' => LeaveRequest::where('approval_2', 'pending')->count(),
            'this_month'    => LeaveRequest::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count(),
            'total_days'    => LeaveRequest::where('approval_2', 'pending')->sum('duration'),
            'avg_days'      => 0,
        ];
        $hrPendingCount   = LeaveRequest::where('approval_1', 'pending')->count();
        $deptPendingCount = LeaveRequest::where('approval_dept', 'pending')->count();

        return view('hr.leave_requests.director-approvals', compact(
            'leaves', 'employees', 'departments', 'leaveTypeLabels', 'stats', 'hrPendingCount', 'deptPendingCount'
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveRequest $leave)
    {
        $leave->load('employee.department');
        $leaveTypeLabels = LeaveRequest::getTypeLabels();
        return view('hr.leave_requests.show', compact('leave', 'leaveTypeLabels'));
    }

    public function serveDocument(LeaveRequest $leave, string $type)
    {
        $column = $type === 'mc' ? 'mc_document' : 'doctor_letter';
        $raw = $leave->$column;

        if (!$raw) {
            abort(404);
        }

        // New format: base64 JSON stored in DB
        $json = json_decode($raw, true);
        if ($json && !empty($json['data'])) {
            return response(base64_decode($json['data']), 200, [
                'Content-Type'        => $json['mime'],
                'Content-Disposition' => 'inline; filename="' . $json['name'] . '"',
            ]);
        }

        // Legacy format: file path stored in public disk
        $path = Storage::disk('public')->path($raw);
        if (!file_exists($path)) {
            abort(404);
        }
        $mime = mime_content_type($path);
        return response(file_get_contents($path), 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveRequest $leave)
    {
        if (!Auth::user()->can('hr.leave.view')) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to delete leave requests.'], 403);
            }
            return redirect()->route('leave_requests.index')->with('error', 'You do not have permission to delete leave requests.');
        }

        $employeeName = $leave->employee->name ?? 'Unknown';
        $leave->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Leave request for <b>{$employeeName}</b> deleted successfully!"]);
        }

        return redirect()
            ->route('leave_requests.index')
            ->with('success', "Leave request for <b>{$employeeName}</b> deleted successfully!");
    }

    /**
     * Sync status daily_attendances berdasarkan leave request yang approved/revoked.
     * Hanya update record yang tidak memiliki clock_in (karyawan memang tidak masuk).
     */
    private function syncDailyAttendanceForLeave(LeaveRequest $leave, string $action): void
    {
        $dates = [];
        $start = Carbon::parse($leave->start_date);
        $end   = Carbon::parse($leave->end_date);

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        if (empty($dates)) {
            return;
        }

        if ($action === 'approved') {
            $status = app(DailyAttendanceService::class)->mapLeaveTypeToStatus($leave->type);

            $query = DailyAttendance::where('employee_id', $leave->employee_id)
                ->whereIn('date', $dates);

            // EARLY_LEAVE & PERMISSION_OUT: karyawan tetap masuk → update meski ada clock_in
            // Tipe lain: hanya update jika karyawan tidak hadir (clock_in NULL)
            if (!in_array($leave->type, ['EARLY_LEAVE', 'PERMISSION_OUT'])) {
                $query->whereNull('clock_in');
            }

            $query->update([
                'status'     => $status,
                'remarks'    => $leave->reason ?: null,
                'updated_by' => auth()->id(),
            ]);

            \Log::info('Daily attendance synced for approved leave', [
                'leave_id'    => $leave->id,
                'employee_id' => $leave->employee_id,
                'dates'       => $dates,
                'status'      => $status,
            ]);
        } elseif ($action === 'revoked') {
            $query = DailyAttendance::where('employee_id', $leave->employee_id)
                ->whereIn('date', $dates);

            if (!in_array($leave->type, ['EARLY_LEAVE', 'PERMISSION_OUT'])) {
                $query->whereNull('clock_in');
            }

            // Kembalikan ke status sebelumnya berdasarkan clock_in & clock_out vs shift
            $query->with('sessionShift')->each(function (DailyAttendance $att) {
                if (! $att->clock_in) {
                    $revertStatus = 'Alpha';
                } elseif ($att->clock_out && $att->sessionShift && $att->sessionShift->end_time) {
                    // Cek apakah clock_out masih lebih awal dari jam pulang shift
                    $clockOutTime    = \Carbon\Carbon::parse($att->clock_out)
                        ->setDate($att->date->year, $att->date->month, $att->date->day);
                    $standardEndTime = \Carbon\Carbon::parse($att->sessionShift->end_time)
                        ->setDate($att->date->year, $att->date->month, $att->date->day);

                    $revertStatus = $clockOutTime->lt($standardEndTime->copy()->subMinutes(5))
                        ? 'Early Leave'
                        : 'Present';
                } else {
                    $revertStatus = 'Present';
                }

                $att->update([
                    'status'     => $revertStatus,
                    'remarks'    => null,
                    'updated_by' => auth()->id(),
                ]);
            });

            \Log::info('Daily attendance reverted for revoked leave', [
                'leave_id'    => $leave->id,
                'employee_id' => $leave->employee_id,
                'dates'       => $dates,
            ]);
        }
    }
}
