<?php

namespace App\Http\Controllers\Hr;

use App\Exports\AttendanceSummaryExport;
use App\Http\Controllers\Controller;
use App\Models\Admin\Department;
use App\Models\Hr\CompanyHoliday;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeWorkPolicy;
use App\Models\NationalHoliday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceSummaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:hr.attendance.view');
    }

    public function index(Request $request)
    {
        // If no month/year specified, default to last month that has data
        if (!$request->filled('month') && !$request->filled('year')) {
            $latest = DailyAttendance::selectRaw('YEAR(date) as y, MONTH(date) as m')
                ->orderByRaw('YEAR(date) DESC, MONTH(date) DESC')
                ->first();
            $month = $latest ? (int) $latest->m : now()->month;
            $year  = $latest ? (int) $latest->y : now()->year;
        } else {
            $month = (int) $request->input('month', now()->month);
            $year  = (int) $request->input('year',  now()->year);
        }

        $month = max(1, min(12, $month));
        $year  = max(2020, min(2099, $year));

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();
        $daysInMonth  = $startOfMonth->daysInMonth;

        $departmentId = $request->input('department_id');
        $departments  = Department::orderBy('name')->get(['id', 'name']);

        $employeeQuery = Employee::whereIn('status', ['active', 'pending_contract'])
            ->whereDoesntHave('department', fn($q) => $q->where('name', 'Party Point'))
            ->orderBy('name');
        if ($departmentId) {
            $employeeQuery->where('department_id', $departmentId);
        }
        $employees = $employeeQuery->get(['id', 'name', 'employee_no', 'department_id', 'saldo_cuti', 'is_production', 'is_leader_capacity', 'status']);

        // National holidays for this year (exclude cuti bersama)
        $nationalHolidays = NationalHoliday::forYear($year)
            ->nationalOnly()
            ->get()
            ->keyBy(fn($h) => Carbon::parse($h->date)->format('Y-m-d'));

        $nationalHolidayDates = $nationalHolidays->mapWithKeys(fn($h, $date) => [$date => $h->name]);

        // Company holidays for this month
        $companyHolidays = CompanyHoliday::forMonth($year, $month)
            ->get()
            ->keyBy(fn($h) => $h->date->format('Y-m-d'));

        // Gunakan pola compound key sama seperti AttendanceLogController (terbukti benar)
        // Key: "employee_id_date" e.g. "132_2026-03-09"
        $dailiesMap = DailyAttendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get(['employee_id', 'date', 'status', 'total_hours'])
            ->groupBy(fn($d) => $d->employee_id . '_' . $d->date->format('Y-m-d'));

        // Build day-level info array: [day => ['dayOfWeek', 'isSunday', 'national', 'company']]
        $dayInfo = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = Carbon::create($year, $month, $d);
            $dateStr = $date->format('Y-m-d');
            $dayInfo[$d] = [
                'date'      => $dateStr,
                'dayName'   => $date->isoFormat('dd'), // Mo,Tu,We,...
                'isSunday'  => $date->dayOfWeek === Carbon::SUNDAY,
                'national'  => $nationalHolidays->get($dateStr),
                'company'   => $companyHolidays->get($dateStr),
            ];
        }

        // Build per-employee summary counts using compound key lookup
        $summary = [];
        foreach ($employees as $emp) {
            $counts = [
                'present' => 0, 'late' => 0, 'alpha' => 0,
                'annual'  => 0, 'sick' => 0, 'leave_other' => 0, 'unpaid' => 0,
            ];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $info   = $dayInfo[$d];
                if ($info['isSunday'] || $info['national'] !== null || $info['company']) {
                    continue;
                }
                $record = $dailiesMap->get($emp->id . '_' . $info['date'])?->first();
                if (!$record) continue;

                match($record->status) {
                    'Present'          => $counts['present']++,
                    'Less Hours'       => $counts['present']++,
                    'Late'             => $counts['late']++,
                    'Late, Less Hours' => $counts['late']++,
                    'Alpha'            => $counts['alpha']++,
                    'Annual Leave'     => $counts['annual']++,
                    'Sick Leave'       => $counts['sick']++,
                    'Unpaid Leave'     => $counts['unpaid']++,
                    default            => $counts['leave_other']++,
                };
            }
            $summary[$emp->id] = $counts;
        }

        // ── Capacity & card stats ────────────────────────────────────────────
        $workingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $info = $dayInfo[$d];
            if (!$info['isSunday'] && !$info['national'] && !$info['company']) {
                $workingDays++;
            }
        }

        $productionEmployeeIds = $employees->where('is_production', true)->pluck('id')->values()->toArray();
        $workPolicies = EmployeeWorkPolicy::whereIn('employee_id', $productionEmployeeIds)
            ->pluck('weekday_hours', 'employee_id');

        $expectedHours = 0.0;
        foreach ($productionEmployeeIds as $empId) {
            $expectedHours += $workingDays * (float) ($workPolicies->get($empId) ?? 8);
        }

        $actualHours = 0.0;
        $totalEmployees = $employees->count();
        $presentIds = []; $alphaIds = []; $leaveIds = []; $mcIds = [];

        foreach ($employees as $emp) {
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $info = $dayInfo[$d];
                if ($info['isSunday'] || $info['national'] || $info['company']) continue;
                $record = $dailiesMap->get($emp->id . '_' . $info['date'])?->first();
                if (!$record) continue;

                if ($emp->is_production) {
                    $actualHours += (float) ($record->total_hours ?? 0);
                }

                match (true) {
                    in_array($record->status, ['Present', 'Late', 'Less Hours', 'Late, Less Hours']) => $presentIds[$emp->id] = true,
                    $record->status === 'Alpha'      => $alphaIds[$emp->id] = true,
                    $record->status === 'Sick Leave' => $mcIds[$emp->id]    = true,
                    in_array($record->status, [
                        'Annual Leave', 'Maternity Leave', 'Paternity Leave', 'Wedding Leave',
                        'Birth Leave', 'Bereavement Leave', 'Child Event Leave', 'Hajj Leave', 'Unpaid Leave',
                    ])                               => $leaveIds[$emp->id] = true,
                    default                          => null,
                };
            }
        }

        $presentCount = count($presentIds);
        $alphaCount   = count($alphaIds);
        $mcCount      = count($mcIds);
        $leaveCount   = count($leaveIds);
        $capacityPct  = $expectedHours > 0 ? round(($actualHours / $expectedHours) * 100) : 0;
        $presentPct   = $totalEmployees > 0 ? round(($presentCount / $totalEmployees) * 100) : 0;
        $alphaPct     = $totalEmployees > 0 ? round(($alphaCount   / $totalEmployees) * 100) : 0;
        $mcPct        = $totalEmployees > 0 ? round(($mcCount      / $totalEmployees) * 100) : 0;
        $leavePct     = $totalEmployees > 0 ? round(($leaveCount   / $totalEmployees) * 100) : 0;

        $productionCount = count($productionEmployeeIds);

        $capacityStats = compact(
            'capacityPct', 'actualHours', 'expectedHours',
            'presentPct', 'presentCount',
            'alphaPct',   'alphaCount',
            'mcPct',      'mcCount',
            'leavePct',   'leaveCount',
            'totalEmployees', 'productionCount'
        );

        // ── Leader Capacity stats ────────────────────────────────────────────
        $leaderEmployeeIds = $employees->where('is_leader_capacity', true)->pluck('id')->values()->toArray();
        $leaderWorkPolicies = EmployeeWorkPolicy::whereIn('employee_id', $leaderEmployeeIds)
            ->pluck('weekday_hours', 'employee_id');

        $leaderExpectedHours = 0.0;
        foreach ($leaderEmployeeIds as $empId) {
            $leaderExpectedHours += $workingDays * (float) ($leaderWorkPolicies->get($empId) ?? 8);
        }

        $leaderActualHours = 0.0;
        foreach ($employees->whereIn('id', $leaderEmployeeIds) as $emp) {
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $info = $dayInfo[$d];
                if ($info['isSunday'] || $info['national'] || $info['company']) continue;
                $record = $dailiesMap->get($emp->id . '_' . $info['date'])?->first();
                if (!$record) continue;
                $leaderActualHours += (float) ($record->total_hours ?? 0);
            }
        }

        $leaderCount      = count($leaderEmployeeIds);
        $leaderCapacityPct = $leaderExpectedHours > 0 ? round(($leaderActualHours / $leaderExpectedHours) * 100) : 0;

        $leaderCapacityStats = compact(
            'leaderCapacityPct', 'leaderActualHours', 'leaderExpectedHours', 'leaderCount'
        );

        // All company holidays (for manage modal) in this month
        $companyHolidaysList = CompanyHoliday::forMonth($year, $month)
            ->with('creator:id,username')
            ->orderBy('date')
            ->get();

        return view('hr.attendance-logs.summary', compact(
            'month', 'year', 'daysInMonth', 'employees',
            'nationalHolidayDates', 'nationalHolidays', 'companyHolidays', 'dailiesMap',
            'dayInfo', 'summary', 'companyHolidaysList', 'startOfMonth',
            'departments', 'departmentId', 'capacityStats', 'leaderCapacityStats'
        ));
    }

    public function exportExcel(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year',  now()->year);
        $month = max(1, min(12, $month));
        $year  = max(2020, min(2099, $year));

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $daysInMonth  = $startOfMonth->daysInMonth;

        $departmentId = $request->input('department_id');

        $employeeQuery = Employee::where('status', 'active')
            ->whereDoesntHave('department', fn($q) => $q->where('name', 'Party Point'))
            ->orderBy('name');
        if ($departmentId) {
            $employeeQuery->where('department_id', $departmentId);
        }
        $employees = $employeeQuery->get(['id', 'name', 'employee_no', 'department_id']);

        $nationalHolidays = NationalHoliday::forYear($year)
            ->nationalOnly()
            ->get()
            ->keyBy(fn($h) => Carbon::parse($h->date)->format('Y-m-d'));

        $companyHolidays = CompanyHoliday::forMonth($year, $month)
            ->get()
            ->keyBy(fn($h) => $h->date->format('Y-m-d'));

        $dailiesMap = DailyAttendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get(['employee_id', 'date', 'status'])
            ->groupBy(fn($d) => $d->employee_id . '_' . $d->date->format('Y-m-d'));

        $dayInfo = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = Carbon::create($year, $month, $d);
            $dateStr = $date->format('Y-m-d');
            $dayInfo[$d] = [
                'date'     => $dateStr,
                'dayName'  => $date->isoFormat('dd'),
                'isSunday' => $date->dayOfWeek === Carbon::SUNDAY,
                'national' => $nationalHolidays->get($dateStr),
                'company'  => $companyHolidays->get($dateStr),
            ];
        }

        $summary = [];
        foreach ($employees as $emp) {
            $counts = ['present' => 0, 'late' => 0, 'alpha' => 0, 'annual' => 0, 'sick' => 0, 'leave_other' => 0, 'unpaid' => 0];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $info = $dayInfo[$d];
                if ($info['isSunday'] || $info['national'] !== null || $info['company']) continue;
                $record = $dailiesMap->get($emp->id . '_' . $info['date'])?->first();
                if (!$record) continue;
                match($record->status) {
                    'Present'          => $counts['present']++,
                    'Less Hours'       => $counts['present']++,
                    'Late'             => $counts['late']++,
                    'Late, Less Hours' => $counts['late']++,
                    'Alpha'            => $counts['alpha']++,
                    'Annual Leave'     => $counts['annual']++,
                    'Sick Leave'       => $counts['sick']++,
                    'Unpaid Leave'     => $counts['unpaid']++,
                    default            => $counts['leave_other']++,
                };
            }
            $summary[$emp->id] = $counts;
        }

        $filename = 'attendance-summary-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.xlsx';

        return Excel::download(
            new AttendanceSummaryExport($employees, $daysInMonth, $dayInfo, $dailiesMap, $summary, $month, $year),
            $filename
        );
    }

    public function updateStatus(Request $request, $employeeId, $date)
    {
        $this->authorize('hr.attendance.edit');

        $request->validate([
            'status' => 'required|in:Present,Late,Less Hours,Early Leave,Permission Out,Excused,Sick Leave,Annual Leave,Maternity Leave,Paternity Leave,Wedding Leave,Birth Leave,Bereavement Leave,Child Event Leave,Hajj Leave,Unpaid Leave,Alpha',
        ]);

        $record = DailyAttendance::where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Attendance record not found.'], 404);
        }

        $record->update([
            'status'    => $request->status,
            'is_locked' => true,
        ]);

        return response()->json(['message' => 'Status updated.', 'status' => $record->status]);
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'date'  => 'required|date',
            'name'  => 'required|string|max:100',
            'type'  => 'required|in:free,paid_leave_deduction,unpaid',
            'notes' => 'nullable|string|max:255',
        ]);

        CompanyHoliday::updateOrCreate(
            ['date' => $request->date],
            [
                'name'       => $request->name,
                'type'       => $request->type,
                'notes'      => $request->notes,
                'created_by' => Auth::id(),
            ]
        );

        return response()->json(['message' => 'Holiday saved.']);
    }

    public function destroyHoliday(CompanyHoliday $companyHoliday)
    {
        $companyHoliday->delete();
        return response()->json(['message' => 'Holiday deleted.']);
    }

    public function storeNationalHoliday(Request $request)
    {
        $request->validate([
            'date'           => 'required|date',
            'name'           => 'required|string|max:100',
            'is_joint_leave' => 'nullable|boolean',
        ]);

        $date = Carbon::parse($request->date);

        NationalHoliday::create([
            'date'           => $request->date,
            'name'           => $request->name,
            'year'           => $date->year,
            'is_joint_leave' => $request->boolean('is_joint_leave'),
        ]);

        return response()->json(['message' => 'National holiday added.']);
    }

    public function updateNationalHoliday(Request $request, NationalHoliday $nationalHoliday)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'date' => 'required|date',
        ]);

        $nationalHoliday->update([
            'name' => $request->name,
            'date' => $request->date,
        ]);

        return response()->json(['message' => 'National holiday updated.']);
    }

    public function destroyNationalHoliday(NationalHoliday $nationalHoliday)
    {
        $nationalHoliday->delete();
        return response()->json(['message' => 'National holiday removed.']);
    }
}
