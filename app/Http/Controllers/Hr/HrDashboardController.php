<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\SessionShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HrDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:hr.dashboard.view');
    }

    public function index()
    {
        $now   = Carbon::now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd   = $now->copy()->endOfMonth()->toDateString();
        $thirtyDaysLater = $now->copy()->addDays(30)->toDateString();

        // ── Employee Metrics ──────────────────────────────────────────────────
        $totalEmployees    = Employee::count();
        $activeEmployees   = Employee::where('status', 'active')->count();
        $terminatedEmployees = Employee::where('status', 'terminated')->count();
        $nearExpiredCount  = Employee::where('status', 'active')
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [$today, $thirtyDaysLater])
            ->count();

        // Employees by department (all employees for chart)
        $byDepartment = DB::table('employees')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('departments.name, COUNT(*) as total')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get();

        // Employees by department (active only)
        $byDepartmentActive = DB::table('employees')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->where('employees.status', 'active')
            ->selectRaw('departments.name, COUNT(*) as employees_count')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('employees_count')
            ->get();

        // Employees by employment_type (active)
        $byEmploymentType = Employee::where('status', 'active')
            ->selectRaw('employment_type, COUNT(*) as total')
            ->groupBy('employment_type')
            ->orderByDesc('total')
            ->get();

        // Employees by citizenship (WNA/WNI)
        $byCitizenship = Employee::where('status', 'active')
            ->selectRaw('citizenship, COUNT(*) as total')
            ->groupBy('citizenship')
            ->get();

        // Gender distribution
        $byGender = Employee::where('status', 'active')
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->get();

        // Near-expired employees list with details
        $nearExpiredList = Employee::with('department')
            ->where('status', 'active')
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [$today, $thirtyDaysLater])
            ->orderBy('contract_end_date')
            ->get();

        $excludeDeptId = 17; // Party Point — gedung berbeda, tidak diikutkan di HR attendance

        // Get all active employees for attendance heatmap sample (exclude Party Point)
        $activeEmployeeList = Employee::with('department')
            ->where('status', 'active')
            ->where('department_id', '!=', $excludeDeptId)
            ->limit(15)
            ->get();

        // ── Attendance Metrics (this month, exclude Party Point) ─────────────
        $attendanceStats = DailyAttendance::whereBetween('date', [$monthStart, $monthEnd])
            ->join('employees', 'daily_attendances.employee_id', '=', 'employees.id')
            ->where('employees.department_id', '!=', $excludeDeptId)
            ->whereNull('employees.deleted_at')
            ->selectRaw("
                SUM(CASE WHEN daily_attendances.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN daily_attendances.status = 'Late'    THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN daily_attendances.status = 'Alpha'   THEN 1 ELSE 0 END) as alpha_count,
                SUM(CASE WHEN daily_attendances.status NOT IN ('Present','Late','Alpha') THEN 1 ELSE 0 END) as other_count,
                COUNT(*) as total_count
            ")
            ->first();

        // Today's attendance — fall back to last recorded day if today has no data yet
        $lastAttendanceDate = DailyAttendance::join('employees', 'daily_attendances.employee_id', '=', 'employees.id')
            ->where('employees.department_id', '!=', $excludeDeptId)
            ->whereNull('employees.deleted_at')
            ->whereDate('daily_attendances.date', $today)
            ->exists()
            ? $today
            : (DailyAttendance::join('employees', 'daily_attendances.employee_id', '=', 'employees.id')
                ->where('employees.department_id', '!=', $excludeDeptId)
                ->whereNull('employees.deleted_at')
                ->max('daily_attendances.date') ?? $today);
        $todayAttendance = DailyAttendance::join('employees', 'daily_attendances.employee_id', '=', 'employees.id')
            ->where('employees.department_id', '!=', $excludeDeptId)
            ->whereNull('employees.deleted_at')
            ->whereDate('daily_attendances.date', $lastAttendanceDate)
            ->whereIn('daily_attendances.status', ['Present', 'Late'])
            ->count();
        $attendanceRate  = $activeEmployees > 0 ? round(($todayAttendance / $activeEmployees) * 100) : 0;
        $attendanceDateLabel = $lastAttendanceDate === $today ? 'Today' : Carbon::parse($lastAttendanceDate)->format('d/m');

        // ── Daily Attendance Trend (exclude Party Point) ─────────────────────
        $dailyTrend = DailyAttendance::whereBetween('daily_attendances.date', [$monthStart, $monthEnd])
            ->join('employees', 'daily_attendances.employee_id', '=', 'employees.id')
            ->where('employees.department_id', '!=', $excludeDeptId)
            ->whereNull('employees.deleted_at')
            ->selectRaw("
                DATE(daily_attendances.date) as day,
                SUM(CASE WHEN daily_attendances.status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN daily_attendances.status = 'Late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN daily_attendances.status = 'Alpha' THEN 1 ELSE 0 END) as alpha
            ")
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get()
            ->map(function($item) {
                return (object)[
                    'day' => Carbon::parse($item->day),
                    'present' => $item->present,
                    'late' => $item->late,
                    'alpha' => $item->alpha,
                ];
            });

        // Top absences this month (active, exclude Party Point)
        $topAbsences = DailyAttendance::with('employee.department')
            ->join('employees', 'daily_attendances.employee_id', '=', 'employees.id')
            ->whereBetween('daily_attendances.date', [$monthStart, $monthEnd])
            ->where('daily_attendances.status', 'Alpha')
            ->where('employees.status', 'active')
            ->where('employees.department_id', '!=', $excludeDeptId)
            ->whereNull('employees.deleted_at')
            ->selectRaw('daily_attendances.employee_id, COUNT(*) as alpha_count')
            ->groupBy('daily_attendances.employee_id')
            ->orderByDesc('alpha_count')
            ->limit(10)
            ->get();

        // Top late this month (active, exclude Party Point)
        $topLate = DailyAttendance::with('employee.department')
            ->join('employees', 'daily_attendances.employee_id', '=', 'employees.id')
            ->whereBetween('daily_attendances.date', [$monthStart, $monthEnd])
            ->where('daily_attendances.status', 'Late')
            ->where('employees.status', 'active')
            ->where('employees.department_id', '!=', $excludeDeptId)
            ->whereNull('employees.deleted_at')
            ->selectRaw('daily_attendances.employee_id, COUNT(*) as late_count')
            ->groupBy('daily_attendances.employee_id')
            ->orderByDesc('late_count')
            ->limit(10)
            ->get();

        // Add present count for top employees
        foreach ($topAbsences as $item) {
            $item->present_count = DailyAttendance::where('employee_id', $item->employee_id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', 'Present')
                ->count();
            $item->late_count = DailyAttendance::where('employee_id', $item->employee_id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', 'Late')
                ->count();
        }

        foreach ($topLate as $item) {
            $item->present_count = DailyAttendance::where('employee_id', $item->employee_id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', 'Present')
                ->count();
            $item->alpha_count = DailyAttendance::where('employee_id', $item->employee_id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', 'Alpha')
                ->count();
        }

        // Get attendance data for heatmap
        $attendanceHeatmap = [];
        $sampleEmployees = $activeEmployeeList->take(10);
        $sampleDates = [];
        $dateLoop = Carbon::parse($monthStart);
        $todayDate = Carbon::parse($today);
        while ($dateLoop <= $todayDate) {
            if ($dateLoop->isWeekday()) {
                $sampleDates[] = $dateLoop->copy();
            }
            $dateLoop->addDay();
        }
        $sampleDates = array_slice($sampleDates, 0, 8);

        foreach ($sampleEmployees as $emp) {
            $row = ['name' => $emp->name];
            foreach ($sampleDates as $date) {
                $attendance = DailyAttendance::where('employee_id', $emp->id)
                    ->whereDate('date', $date)
                    ->first();
                $status = $attendance ? $attendance->status : null;
                $statusMap = [
                    'Present' => 'P',
                    'Late' => 'L',
                    'Alpha' => 'A',
                    'Permission' => 'I',
                ];
                $row[$date->format('Y-m-d')] = $statusMap[$status] ?? '-';
            }
            $row['present_total'] = DailyAttendance::where('employee_id', $emp->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', 'Present')
                ->count();
            $row['late_total'] = DailyAttendance::where('employee_id', $emp->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', 'Late')
                ->count();
            $attendanceHeatmap[] = $row;
        }

        // ── Leave Metrics ─────────────────────────────────────────────────────
        $pendingLeaveHr      = LeaveRequest::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->where('approval_1', 'pending')->count();
        $pendingLeaveDir     = LeaveRequest::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->where('approval_1', 'approved')->where('approval_2', 'pending')->count();
        $approvedLeaves      = LeaveRequest::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->where('approval_1', 'approved')->where('approval_2', 'approved')->count();
        $rejectedLeaves      = LeaveRequest::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->where(function($q) {
                $q->where('approval_1','rejected')->orWhere('approval_2','rejected');
            })->count();

        $leaveByType = LeaveRequest::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        $recentLeaveRequests = LeaveRequest::with('employee')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // ── Overtime Metrics ──────────────────────────────────────────────────
        $pendingOT    = OvertimeRequest::where('hr_approval_status', 'pending')->count();
        $thisMonthOT  = OvertimeRequest::whereMonth('start_time', $now->month)
            ->whereYear('start_time', $now->year)->count();
        $totalOTHours = OvertimeRequest::where('hr_approval_status', 'approved')
            ->whereMonth('start_time', $now->month)
            ->whereYear('start_time', $now->year)
            ->sum('net_hours');

        // OT by month for trend
        $otMonthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $hours = OvertimeRequest::where('hr_approval_status', 'approved')
                ->whereYear('start_time', $month->year)
                ->whereMonth('start_time', $month->month)
                ->sum('net_hours');
            $otMonthlyTrend[] = [
                'month' => $month->format('M'),
                'hours' => round($hours, 1),
            ];
        }

        // OT by type
        $otByType = OvertimeRequest::where('hr_approval_status', 'approved')
            ->whereMonth('start_time', $now->month)
            ->whereYear('start_time', $now->year)
            ->selectRaw('ot_code, COUNT(*) as total')
            ->groupBy('ot_code')
            ->get();

        // ── Session Shifts ────────────────────────────────────────────────────
        $totalShifts  = SessionShift::where('is_active', true)->count();
        $shiftsByType = SessionShift::where('is_active', true)
            ->selectRaw('type_of_shift, COUNT(*) as total')
            ->groupBy('type_of_shift')
            ->get();

        return view('hr.dashboard.index', compact(
            'now', 'totalEmployees', 'activeEmployees', 'terminatedEmployees',
            'nearExpiredCount', 'byDepartment', 'byDepartmentActive', 'byEmploymentType', 
            'byCitizenship', 'byGender', 'nearExpiredList', 'activeEmployeeList',
            'attendanceStats', 'todayAttendance', 'attendanceRate', 'attendanceDateLabel',
            'dailyTrend', 'topAbsences', 'topLate', 'attendanceHeatmap', 'sampleDates',
            'pendingLeaveHr', 'pendingLeaveDir', 'approvedLeaves', 'rejectedLeaves',
            'leaveByType', 'recentLeaveRequests', 'pendingOT', 'thisMonthOT', 'totalOTHours',
            'otMonthlyTrend', 'otByType', 'totalShifts', 'shiftsByType'
        ));
    }
}