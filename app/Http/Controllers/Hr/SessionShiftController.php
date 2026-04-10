<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\SessionShift;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SessionShiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!in_array(Auth::user()->role, ['super_admin', 'admin_hr', 'admin'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $shifts = SessionShift::with(['department', 'employee'])
            ->orderByRaw('department_id IS NULL DESC')
            ->orderBy('department_id')
            ->orderBy('for_wna')
            ->orderBy('start_time')
            ->get();

        $departments = Department::orderBy('name')->get();

        return view('hr.session-shifts.index', compact('shifts', 'departments'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $employees   = Employee::where('status', 'active')->orderBy('name')->get();
        return view('hr.session-shifts.form', compact('departments', 'employees'));
    }

    /**
     * Ubah position_keywords (string dipisah koma) jadi array, strip whitespace.
     * Return null jika kosong.
     */
    private function parsePositionKeywords(?string $raw): ?array
    {
        if (blank($raw)) return null;
        $keywords = array_values(array_filter(array_map('trim', explode(',', $raw))));
        return empty($keywords) ? null : array_map('strtolower', $keywords);
    }

    /**
     * Normalize all time fields to H:i (strip seconds if browser sends H:i:s).
     */
    private function normalizeTimeFields(Request $request): void
    {
        $timeFields = ['start_time', 'end_time', 'break_start', 'break_end', 'break2_start', 'break2_end', 'detect_from', 'detect_until'];
        foreach ($timeFields as $field) {
            $val = $request->input($field);
            if ($val && preg_match('/^\d{2}:\d{2}:\d{2}$/', $val)) {
                $request->merge([$field => substr($val, 0, 5)]);
            }
        }
    }

    public function store(Request $request)
    {
        $this->normalizeTimeFields($request);

        $data = $request->validate([
            'department_id'     => 'nullable|exists:departments,id',
            'employee_id'       => 'nullable|exists:employees,id',
            'type_of_shift'     => 'required|string|max:10',
            'start_time'        => 'required|date_format:H:i',
            'end_time'          => 'required|date_format:H:i',
            'break_start'       => 'nullable|date_format:H:i',
            'break_end'         => 'nullable|date_format:H:i',
            'break2_start'      => 'nullable|date_format:H:i',
            'break2_end'        => 'nullable|date_format:H:i',
            'for_wna'           => 'boolean',
            'detect_from'       => 'required|date_format:H:i',
            'detect_until'      => 'required|date_format:H:i',
            'is_active'         => 'boolean',
            'applicable_days'   => 'nullable|array',
            'applicable_days.*' => 'integer|between:1,7',
            'position_keywords' => 'nullable|string|max:500',
        ]);

        $data['for_wna']           = $request->boolean('for_wna');
        $data['is_active']         = $request->boolean('is_active', true);
        $data['applicable_days']   = empty($data['applicable_days']) ? null : array_map('intval', $data['applicable_days']);
        $data['position_keywords'] = $this->parsePositionKeywords($request->input('position_keywords'));

        SessionShift::create($data);

        return redirect()->route('session-shifts.index')
            ->with('success', 'Shift created successfully.');
    }

    public function edit(SessionShift $sessionShift)
    {
        $departments = Department::orderBy('name')->get();
        $employees   = Employee::where('status', 'active')->orderBy('name')->get();
        return view('hr.session-shifts.form', ['shift' => $sessionShift, 'departments' => $departments, 'employees' => $employees]);
    }

    public function update(Request $request, SessionShift $sessionShift)
    {
        $this->normalizeTimeFields($request);

        $data = $request->validate([
            'department_id'     => 'nullable|exists:departments,id',
            'employee_id'       => 'nullable|exists:employees,id',
            'type_of_shift'     => 'required|string|max:10',
            'start_time'        => 'required|date_format:H:i',
            'end_time'          => 'required|date_format:H:i',
            'break_start'       => 'nullable|date_format:H:i',
            'break_end'         => 'nullable|date_format:H:i',
            'break2_start'      => 'nullable|date_format:H:i',
            'break2_end'        => 'nullable|date_format:H:i',
            'for_wna'           => 'boolean',
            'detect_from'       => 'required|date_format:H:i',
            'detect_until'      => 'required|date_format:H:i',
            'is_active'         => 'boolean',
            'applicable_days'   => 'nullable|array',
            'applicable_days.*' => 'integer|between:1,7',
            'position_keywords' => 'nullable|string|max:500',
        ]);

        $data['for_wna']           = $request->boolean('for_wna');
        $data['is_active']         = $request->boolean('is_active', true);
        $data['applicable_days']   = empty($data['applicable_days']) ? null : array_map('intval', $data['applicable_days']);
        $data['position_keywords'] = $this->parsePositionKeywords($request->input('position_keywords'));

        $sessionShift->update($data);

        return redirect()->route('session-shifts.index')
            ->with('success', 'Shift updated successfully.');
    }

    public function destroy(SessionShift $sessionShift)
    {
        $sessionShift->delete();
        return redirect()->route('session-shifts.index')
            ->with('success', 'Shift deleted.');
    }

    public function clearBreak2(SessionShift $sessionShift)
    {
        $sessionShift->update([
            'break2_start' => null,
            'break2_end'   => null,
        ]);

        return redirect()->route('session-shifts.index')
            ->with('success', "Break 2 cleared for shift {$sessionShift->type_of_shift}.");
    }

    public function liveMonitor(Request $request)
    {
        $date         = $request->input('date', today()->toDateString());
        $departmentId = $request->input('department_id');

        $attendanceQuery = DailyAttendance::with(['employee.department', 'sessionShift'])
            ->whereDate('date', $date)
            ->whereNotNull('clock_in');

        if ($departmentId) {
            $attendanceQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        $attendances = $attendanceQuery->orderBy('clock_in')->get();

        $leaveQuery = LeaveRequest::with('employee.department')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('approval_1', 'approved');

        if ($departmentId) {
            $leaveQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        $leaves = $leaveQuery->get();

        // Exclude employees already counted in attendance
        $attendanceEmployeeIds = $attendances->pluck('employee_id')->toArray();
        $leaves = $leaves->filter(fn($l) => !in_array($l->employee_id, $attendanceEmployeeIds));

        // Active employees who haven't clocked in and are not on leave
        $leaveEmployeeIds = $leaves->pluck('employee_id')->toArray();
        $excludedIds      = array_merge($attendanceEmployeeIds, $leaveEmployeeIds);

        $notClockedInQuery = Employee::with('department')
            ->where('status', 'active')
            ->whereNotIn('id', $excludedIds);

        if ($departmentId) {
            $notClockedInQuery->where('department_id', $departmentId);
        }

        $notClockedIn = $notClockedInQuery->orderBy('name')->get();

        $now            = Carbon::now();
        $currentMinutes = $now->hour * 60 + $now->minute;
        $departments    = Department::orderBy('name')->get();
        $isToday        = $date === today()->toDateString();

        return view('hr.session-shifts.live-monitor', compact(
            'attendances', 'leaves', 'notClockedIn', 'departments', 'date',
            'departmentId', 'now', 'currentMinutes', 'isToday'
        ));
    }
}
