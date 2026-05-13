<?php

namespace App\Http\Controllers\Timing;

use App\Http\Controllers\Controller;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Admin\Department;
use App\Models\Production\Timing;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LiveWorkstationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $date         = $request->input('date', today('Asia/Jakarta')->toDateString());
        $type         = $request->input('type');
        $departmentId = $request->input('department_id');

        // Resolve department_id from type param (when coming from a specific monitor)
        if (!$departmentId && $type) {
            $dept = match ($type) {
                'costume'      => Department::where('name', 'LIKE', '%costume%')
                                      ->orWhere('name', 'LIKE', '%sewing%')->first(),
                'mascot'       => Department::where('name', 'LIKE', '%mascot%')->first(),
                'animatronics' => Department::where('name', 'LIKE', '%animatronic%')
                                      ->orWhere('name', 'LIKE', '%animation%')->first(),
                default        => null,
            };
            if ($dept) $departmentId = $dept->id;
        }

        // Attendance
        $attendanceQuery = DailyAttendance::with(['employee.department', 'sessionShift'])
            ->whereDate('date', $date)
            ->whereNotNull('clock_in');
        if ($departmentId) {
            $attendanceQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }
        $attendances = $attendanceQuery->orderBy('clock_in')->get();

        // Leave
        $leaveQuery = LeaveRequest::with('employee.department')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('approval_1', 'approved');
        if ($departmentId) {
            $leaveQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }
        $leaves = $leaveQuery->get();

        $attendanceEmployeeIds = $attendances->pluck('employee_id')->toArray();
        $leaves                = $leaves->filter(fn($l) => !in_array($l->employee_id, $attendanceEmployeeIds));

        // Not clocked in
        $leaveEmployeeIds  = $leaves->pluck('employee_id')->toArray();
        $excludedIds       = array_merge($attendanceEmployeeIds, $leaveEmployeeIds);
        $notClockedInQuery = Employee::with('department')
            ->active()
            ->whereNotIn('id', $excludedIds);
        if ($departmentId) {
            $notClockedInQuery->where('department_id', $departmentId);
        }
        $notClockedIn = $notClockedInQuery->orderBy('name')->get();

        // Timing sessions for today grouped by employee
        $timingQuery = Timing::with(['jobOrder.project'])
            ->whereDate('tanggal', $date)
            ->whereIn('status', ['on progress', 'frozen', 'paused', 'complete'])
            ->where(fn($q) => $q->whereNotNull('started_at')->orWhereNotNull('start_time'));
        if ($departmentId) {
            $timingQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }
        $timingsByEmployee = $timingQuery->orderBy('started_at')->get()->groupBy('employee_id');

        $now            = Carbon::now('Asia/Jakarta');
        $currentMinutes = $now->hour * 60 + $now->minute;
        $departments    = Department::orderBy('name')->get();
        $isToday        = $date === today('Asia/Jakarta')->toDateString();

        // Count employees with at least one active ('on progress') timing
        $onJobCount = $attendances->filter(function ($att) use ($timingsByEmployee) {
            $empTimings = $timingsByEmployee->get($att->employee_id, collect());
            return $empTimings->where('status', 'on progress')->isNotEmpty();
        })->count();

        return view('timing.live-workstation.index', compact(
            'attendances', 'leaves', 'notClockedIn', 'departments',
            'date', 'departmentId', 'type', 'now', 'currentMinutes',
            'isToday', 'timingsByEmployee', 'onJobCount'
        ));
    }
}
