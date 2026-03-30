<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use App\Models\Production\JobOrder;
use App\Models\Admin\User;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\DailyAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Hr\ApprovalMatrix;
use App\Models\Hr\ApprovalTransaction;
use App\Services\ApprovalService;

class OvertimeRequestController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = OvertimeRequest::with(['employee', 'department', 'jobOrder', 'hrApprover', 'directorApprover']);

        // Filter
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('ot_code')) {
            $query->where('ot_code', $request->ot_code);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_time', [$request->start_date, $request->end_date . ' 23:59:59']);
        }

        $overtimeRequests = $query->latest()->paginate(15);

        // Hitung match status untuk setiap request
        foreach ($overtimeRequests as $req) {
            $attendance = AttendanceLog::where('employee_id', $req->employee_id)
                            ->whereDate('date', $req->start_time->toDateString())
                            ->first();

            if (!$attendance) {
                $req->match_status = 'missing';
                $req->match_status_text = 'No Attendance';
                $req->match_status_class = 'secondary';
            } else {
                $otEnd = $req->end_time->format('H:i');
                $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null;
                
                if (!$clockOut) {
                    $req->match_status = 'missing_clockout';
                    $req->match_status_text = 'No Clock Out';
                    $req->match_status_class = 'warning';
                } elseif ($otEnd === $clockOut) {
                    $req->match_status = 'match';
                    $req->match_status_text = 'Match';
                    $req->match_status_class = 'success';
                } else {
                    $req->match_status = 'mismatch';
                    $req->match_status_text = 'Mismatch';
                    $req->match_status_class = 'danger';
                }
            }
        }

        $employees = Employee::select('id', 'employee_no', 'name')->get();
        $departments = Department::select('id', 'name')->get();

        return view('hr.overtime-requests.index', compact('overtimeRequests', 'employees', 'departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::all();
        $jobOrders = JobOrder::with('department')->get();
        return view('hr.overtime-requests.create', compact('employees', 'jobOrders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'   => 'required|array|min:1',
            'employee_id.*' => 'exists:employees,id',
            'job_order_id'  => [
                'required',
                'exists:job_orders,id',
                function ($attribute, $value, $fail) {
                    $jobOrder = JobOrder::find($value);
                    if (!$jobOrder || is_null($jobOrder->department_id)) {
                        $fail('Job order yang dipilih tidak memiliki departemen.');
                    }
                },
            ],
            'reason'        => 'required|string',
            'ot_code'       => 'required|in:Normal Day,Sunday,Public Holiday',
            'start_time'    => 'required|date',
            'end_time'      => 'required|date|after:start_time',
        ]);

        $start      = Carbon::parse($validated['start_time']);
        $end        = Carbon::parse($validated['end_time']);
        $totalHours = $end->diffInMinutes($start) / 60;

        $breakDeduction = 0;
        if ($totalHours >= 3) {
            $breakDeduction = floor($totalHours / 3) * 0.5;
        }
        $netHours = $totalHours - $breakDeduction;

        $jobOrder     = JobOrder::find($validated['job_order_id']);
        $departmentId = $jobOrder->department_id;

        $hasMatrix = ApprovalMatrix::where('module', 'overtime')->exists();

        foreach ($validated['employee_id'] as $employeeId) {
            $ot = OvertimeRequest::create([
                'uid'                      => (string) Str::uuid(),
                'employee_id'              => $employeeId,
                'department_id'            => $departmentId,
                'job_order_id'             => $validated['job_order_id'],
                'reason'                   => $validated['reason'],
                'ot_code'                  => $validated['ot_code'],
                'start_time'               => $validated['start_time'],
                'end_time'                 => $validated['end_time'],
                'total_hours'              => $totalHours,
                'break_deduction'          => $breakDeduction,
                'net_hours'                => $netHours,
                'status'                   => 'submitted',
                'hr_approval_status'       => 'pending',
                'director_approval_status' => 'pending',
                'is_passed'                => false,
            ]);

            if ($hasMatrix) {
                $this->approvalService->initiate('overtime', $ot->id);
            }
        }

        $count = count($validated['employee_id']);
        return redirect()->route('overtime-requests.index')
                         ->with('success', "Overtime request created for {$count} employee(s).");
    }

    /**
     * Display the specified resource.
     */
    public function show(OvertimeRequest $overtimeRequest)
    {
        $overtimeRequest->load(['employee', 'department', 'jobOrder', 'hrApprover', 'directorApprover']);
        return view('hr.overtime-requests.show', compact('overtimeRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->status === 'rejected') {
            return redirect()->route('overtime-requests.show', $overtimeRequest)
                             ->with('error', 'Cannot edit a rejected request.');
        }

        $employees = Employee::all();
        $jobOrders = JobOrder::with('department')->get();
        return view('hr.overtime-requests.edit', compact('overtimeRequest', 'employees', 'jobOrders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->status === 'rejected') {
            return redirect()->route('overtime-requests.show', $overtimeRequest)
                             ->with('error', 'Cannot edit a rejected request.');
        }

        $validated = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'job_order_id'  => [
                'required',
                'exists:job_orders,id',
                function ($attribute, $value, $fail) {
                    $jobOrder = JobOrder::find($value);
                    if (!$jobOrder || is_null($jobOrder->department_id)) {
                        $fail('Job order yang dipilih tidak memiliki departemen.');
                    }
                },
            ],
            'reason'        => 'required|string',
            'ot_code'       => 'required|in:Normal Day,Sunday,Public Holiday',
            'start_time'    => 'required|date',
            'end_time'      => 'required|date|after:start_time',
        ]);

        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $totalHours = $end->diffInMinutes($start) / 60;

        $breakDeduction = 0;
        if ($totalHours >= 3) {
            $breakDeduction = floor($totalHours / 3) * 0.5;
        }
        $netHours = $totalHours - $breakDeduction;

        $jobOrder = JobOrder::find($validated['job_order_id']);
        $departmentId = $jobOrder->department_id; // sudah dipastikan tidak null

        $hrWasApproved = $overtimeRequest->hr_approval_status === 'approved';

        $overtimeRequest->update([
            'employee_id'     => $validated['employee_id'],
            'department_id'   => $departmentId,
            'job_order_id'    => $validated['job_order_id'],
            'reason'          => $validated['reason'],
            'ot_code'         => $validated['ot_code'],
            'start_time'      => $validated['start_time'],
            'end_time'        => $validated['end_time'],
            'total_hours'     => $totalHours,
            'break_deduction' => $breakDeduction,
            'net_hours'       => $netHours,
        ]);

        // Jika HR sudah approve sebelum edit → reset approval HR & flag untuk notice
        if ($hrWasApproved) {
            $overtimeRequest->hr_approval_status       = null;
            $overtimeRequest->hr_approved_by           = null;
            $overtimeRequest->hr_approved_at           = null;
            $overtimeRequest->director_approval_status = null;
            $overtimeRequest->director_approved_by     = null;
            $overtimeRequest->director_approved_at     = null;
            $overtimeRequest->edited_after_hr_approval = true;
            $overtimeRequest->status                   = 'submitted';
            $overtimeRequest->save();
        }

        return redirect()->route('overtime-requests.show', $overtimeRequest)
                         ->with('success', 'Overtime request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->status !== 'draft') {
            return redirect()->route('overtime-requests.index')
                             ->with('error', 'Cannot delete non-draft request.');
        }
        $overtimeRequest->delete();
        return redirect()->route('overtime-requests.index')
                         ->with('success', 'Overtime request deleted.');
    }

    /**
     * Submit (ubah dari draft ke submitted)
     */
    public function submit(OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->status !== 'draft') {
            return back()->with('error', 'Only draft can be submitted.');
        }
        $overtimeRequest->status = 'submitted';
        $overtimeRequest->save();

        // Inisiasi audit trail di approval_transactions (jika matrix sudah dikonfigurasi)
        if (ApprovalMatrix::where('module', 'overtime')->exists()) {
            $this->approvalService->initiate('overtime', $overtimeRequest->id);
        }

        return back()->with('success', 'Request submitted for approval.');
    }

    /**
     * Approval HR
     */
    public function approveHr(Request $request, OvertimeRequest $overtimeRequest)
    {
        $request->validate([
            'action' => 'required|in:approve,reject'
        ]);

        if ($overtimeRequest->hr_approval_status !== 'pending') {
            return back()->with('error', 'HR approval already processed.');
        }

        // Cek role dari approval_matrix level 1 (tidak hardcode)
        $user        = auth()->user();
        $requiredRole = ApprovalMatrix::where('module', 'overtime')->where('level', 1)->value('role') ?? 'admin_hr';
        if ($user->role !== $requiredRole && $user->role !== 'super_admin') {
            return back()->with('error', "Hanya role [{$requiredRole}] yang dapat melakukan HR approval.");
        }

        $newStatus = $request->action === 'approve' ? 'approved' : 'rejected';
        $overtimeRequest->hr_approval_status       = $newStatus;
        $overtimeRequest->hr_approved_by           = $user->id;
        $overtimeRequest->hr_approved_at           = now();
        $overtimeRequest->edited_after_hr_approval = false;

        $overtimeRequest->updateOverallStatus();
        $overtimeRequest->save();

        // Log ke approval_transactions
        ApprovalTransaction::create([
            'module'       => 'overtime',
            'reference_id' => $overtimeRequest->id,
            'level'        => 1,
            'approved_by'  => $user->id,
            'status'       => $newStatus,
            'approved_at'  => now(),
        ]);

        return back()->with('success', 'HR approval updated.');
    }

    /**
     * Approval Direktur (independen, tanpa perlu HR approve dulu)
     */
    public function approveDirector(Request $request, OvertimeRequest $overtimeRequest)
    {
        $request->validate([
            'action' => 'required|in:approve,reject'
        ]);

        if ($overtimeRequest->director_approval_status !== 'pending') {
            return back()->with('error', 'Director approval already processed.');
        }

        // Cek semua role yang diizinkan dari approval_matrix level 2 (role utama + delegate)
        $user         = auth()->user();
        $level2Matrix = ApprovalMatrix::where('module', 'overtime')->where('level', 2)->first();
        $allowedRoles = $level2Matrix ? $level2Matrix->getAllowedRoles() : ['director', 'admin_hr'];
        if (!in_array($user->role, $allowedRoles) && $user->role !== 'super_admin') {
            $label = implode(' / ', $allowedRoles);
            return back()->with('error', "Hanya role [{$label}] yang dapat melakukan Director approval.");
        }

        $newStatus = $request->action === 'approve' ? 'approved' : 'rejected';
        $overtimeRequest->director_approval_status = $newStatus;
        $overtimeRequest->director_approved_by     = $user->id;
        $overtimeRequest->director_approved_at     = now();

        $overtimeRequest->updateOverallStatus();
        $overtimeRequest->save();

        // Log ke approval_transactions
        ApprovalTransaction::create([
            'module'       => 'overtime',
            'reference_id' => $overtimeRequest->id,
            'level'        => 2,
            'approved_by'  => $user->id,
            'status'       => $newStatus,
            'approved_at'  => now(),
        ]);

        return back()->with('success', 'Director approval updated.');
    }

    /**
     * Calculate Pay (opsional)
     */
    public function calculatePay(OvertimeRequest $overtimeRequest)
    {
        $employee = $overtimeRequest->employee;
        $rate = $employee->hourly_rate ?? 0;

        $netHours = $overtimeRequest->net_hours;
        $otCode = $overtimeRequest->ot_code;

        $totalPay = 0;

        if ($otCode === 'Normal Day') {
            if ($netHours <= 1) {
                $totalPay = $netHours * $rate * 1.5;
            } else {
                $totalPay = (1 * $rate * 1.5) + (($netHours - 1) * $rate * 2);
            }
        } else {
            if ($netHours <= 7) {
                $totalPay = $netHours * $rate * 2;
            } elseif ($netHours <= 8) {
                $totalPay = (7 * $rate * 2) + (($netHours - 7) * $rate * 3);
            } else {
                $totalPay = (7 * $rate * 2) + (1 * $rate * 3) + (($netHours - 8) * $rate * 4);
            }
        }

        return response()->json(['total_pay' => $totalPay]);
    }

    /**
     * HR Approval List (pending HR)
     */
    public function hrApprovals(Request $request)
    {
        if (!in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin'])) {
            abort(403);
        }

        $query = OvertimeRequest::with(['employee', 'department', 'jobOrder', 'hrApprover', 'directorApprover'])
                    ->whereIn('status', ['submitted', 'draft'])
                    ->where(function ($q) {
                        $q->where('hr_approval_status', 'pending')
                          ->orWhereNull('hr_approval_status');
                    });

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('ot_code')) {
            $query->where('ot_code', $request->ot_code);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_time', [$request->start_date, $request->end_date . ' 23:59:59']);
        }

        $overtimeRequests = $query->latest()->paginate(15);

        $employees = Employee::select('id', 'employee_no', 'name')->get();
        $departments = Department::select('id', 'name')->get();

        $stats = [
            'total_pending' => OvertimeRequest::whereIn('status', ['submitted', 'draft'])
                                ->where(function ($q) { $q->where('hr_approval_status', 'pending')->orWhereNull('hr_approval_status'); })
                                ->count(),
            'this_month' => OvertimeRequest::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count(),
            'total_hours' => OvertimeRequest::whereIn('status', ['submitted', 'draft'])
                                ->where(function ($q) { $q->where('hr_approval_status', 'pending')->orWhereNull('hr_approval_status'); })
                                ->sum('net_hours'),
            'avg_days' => 0,
        ];

        $directorPendingCount = OvertimeRequest::whereIn('status', ['submitted', 'draft'])
                                ->where(function ($q) { $q->where('director_approval_status', 'pending')->orWhereNull('director_approval_status'); })
                                ->count();

        return view('hr.overtime-requests.hr-approvals', compact('overtimeRequests', 'employees', 'departments', 'stats', 'directorPendingCount'));
    }

    /**
     * Director Approval List (pending director)
     */
    public function directorApprovals(Request $request)
    {
        // Cek akses dari approval_matrix level 2 (role utama + delegate + super_admin)
        $level2Matrix = ApprovalMatrix::where('module', 'overtime')->where('level', 2)->first();
        $allowedRoles = $level2Matrix
            ? array_merge($level2Matrix->getAllowedRoles(), ['super_admin'])
            : ['director', 'admin_hr', 'super_admin'];

        if (!in_array(auth()->user()->role, $allowedRoles)) {
            abort(403);
        }

        $query = OvertimeRequest::with(['employee', 'department', 'jobOrder', 'hrApprover', 'directorApprover'])
                    ->whereIn('status', ['submitted', 'draft'])
                    ->where(function ($q) {
                        $q->where('director_approval_status', 'pending')
                          ->orWhereNull('director_approval_status');
                    });

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('ot_code')) {
            $query->where('ot_code', $request->ot_code);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_time', [$request->start_date, $request->end_date . ' 23:59:59']);
        }

        $overtimeRequests = $query->latest()->paginate(15);

        $employees = Employee::select('id', 'employee_no', 'name')->get();
        $departments = Department::select('id', 'name')->get();

        $stats = [
            'total_pending' => OvertimeRequest::whereIn('status', ['submitted', 'draft'])
                                ->where(function ($q) { $q->where('director_approval_status', 'pending')->orWhereNull('director_approval_status'); })
                                ->count(),
            'this_month' => OvertimeRequest::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count(),
            'total_hours' => OvertimeRequest::whereIn('status', ['submitted', 'draft'])
                                ->where(function ($q) { $q->where('director_approval_status', 'pending')->orWhereNull('director_approval_status'); })
                                ->sum('net_hours'),
            'avg_days' => 0,
        ];

        $hrPendingCount = OvertimeRequest::where('status', 'submitted')
                            ->where('hr_approval_status', 'pending')
                            ->count();

        return view('hr.overtime-requests.director-approvals', compact('overtimeRequests', 'employees', 'departments', 'stats', 'hrPendingCount'));
    }

    /**
     * Tampilkan perbandingan overtime request dengan attendance log
     */
    public function attendanceComparison(Request $request)
    {
        if (!in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin', 'admin'])) {
            abort(403);
        }

        $query = OvertimeRequest::with(['employee', 'jobOrder', 'department'])
                    ->where('status', 'approved')
                    ->where('hr_approval_status', 'approved')
                    ->where('director_approval_status', 'approved');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_time', [$request->start_date, $request->end_date . ' 23:59:59']);
        }
        if ($request->has('passed') && $request->passed !== '') {
            $query->where('is_passed', (bool) $request->passed);
        }

        $overtimeRequests = $query->latest()->paginate(20);

        $stats = [
            'total' => 0,
            'match' => 0,
            'mismatch' => 0,
            'no_attendance' => 0,
            'no_clockout' => 0,
        ];

        foreach ($overtimeRequests as $req) {
            $attendance = AttendanceLog::where('employee_id', $req->employee_id)
                            ->whereDate('date', $req->start_time->toDateString())
                            ->first();
            $req->attendance = $attendance;

            if (!$attendance) {
                $req->match_status = 'missing';
                $req->match_status_text = 'No Attendance';
                $req->match_status_class = 'secondary';
                $stats['no_attendance']++;
            } else {
                $otEnd = $req->end_time->format('H:i');
                $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null;
                
                if (!$clockOut) {
                    $req->match_status = 'missing_clockout';
                    $req->match_status_text = 'No Clock Out';
                    $req->match_status_class = 'warning';
                    $stats['no_clockout']++;
                } elseif ($otEnd === $clockOut) {
                    $req->match_status = 'match';
                    $req->match_status_text = 'Match';
                    $req->match_status_class = 'success';
                    $stats['match']++;
                } else {
                    $req->match_status = 'mismatch';
                    $req->match_status_text = 'Mismatch';
                    $req->match_status_class = 'danger';
                    $stats['mismatch']++;
                }
            }
            $stats['total']++;
        }

        $employees = Employee::select('id', 'name')->get();

        return view('hr.overtime-requests.attendance-comparison', compact('overtimeRequests', 'employees', 'stats'));
    }

    /**
     * Update clock_in / clock_out di daily_attendances dari halaman OT comparison
     */
    public function updateAttendance(Request $request, OvertimeRequest $overtimeRequest)
    {
        if (!in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin'])) {
            abort(403);
        }

        $request->validate([
            'clock_in'  => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
        ]);

        $date       = $overtimeRequest->start_time->toDateString();
        $employeeId = $overtimeRequest->employee_id;

        $daily = DailyAttendance::firstOrNew([
            'employee_id' => $employeeId,
            'date'        => $date,
        ]);

        if ($request->filled('clock_in'))  $daily->clock_in  = $request->clock_in;
        if ($request->filled('clock_out')) $daily->clock_out = $request->clock_out;

        // Recalculate total_hours if both present
        if ($daily->clock_in && $daily->clock_out) {
            $in  = Carbon::parse($daily->clock_in);
            $out = Carbon::parse($daily->clock_out);
            $daily->total_hours = round($out->diffInMinutes($in) / 60, 2);
        }

        $daily->updated_by = auth()->id();
        $daily->save();

        // Also sync attendance_logs for consistency
        $log = AttendanceLog::where('employee_id', $employeeId)->whereDate('date', $date)->first();
        if ($log) {
            if ($request->filled('clock_in'))  $log->clock_in  = $request->clock_in;
            if ($request->filled('clock_out')) $log->clock_out = $request->clock_out;
            if ($log->clock_in && $log->clock_out) {
                $log->total_hours = round(Carbon::parse($log->clock_out)->diffInMinutes(Carbon::parse($log->clock_in)) / 60, 2);
            }
            $log->save();
        }

        return back()->with('success', "Attendance updated for {$overtimeRequest->employee->name} on {$date}.");
    }

    /**
     * Toggle pass status (HR only) - dengan perhitungan otomatis
     */
    public function togglePass(Request $request, OvertimeRequest $overtimeRequest)
    {
        if (!in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin'])) {
            abort(403);
        }

        $oldValue = $overtimeRequest->is_passed;
        $newValue = !$oldValue;

        if ($newValue) {
            if ($overtimeRequest->payDetail()->exists()) {
                $overtimeRequest->is_passed = true;
                $overtimeRequest->save();
                return back()->with('success', 'Request ditandai pass (data perhitungan sudah ada).');
            }

            if (!$overtimeRequest->employee || $overtimeRequest->employee->salary <= 0) {
                return back()->with('error', 'Tidak dapat menandai pass karena karyawan tidak memiliki salary.');
            }

            try {
                $overtimeRequest->calculateAndSavePayDetail();
                $overtimeRequest->is_passed = true;
                $overtimeRequest->save();
                return back()->with('success', 'Request ditandai pass dan perhitungan berhasil disimpan.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal menghitung: ' . $e->getMessage());
            }
        } else {
            if ($overtimeRequest->payDetail) {
                $overtimeRequest->payDetail->delete();
            }
            $overtimeRequest->is_passed = false;
            $overtimeRequest->save();
            return back()->with('success', 'Pass dibatalkan dan data perhitungan dihapus.');
        }
    }
}