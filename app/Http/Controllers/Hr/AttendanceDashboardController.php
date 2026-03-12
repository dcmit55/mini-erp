<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\ShiftAnomaly;
use App\Models\Hr\BreakEvent;
use App\Models\Hr\NextDaySchedule;
use App\Models\Hr\OvertimeRequest;
use App\Services\Hr\AttendanceOverrideService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceDashboardController extends Controller
{
    public function __construct(
        private AttendanceOverrideService $overrideService
    ) {}

    /**
     * Dashboard ringkasan harian untuk HR.
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        // Ringkasan status jam kerja hari ini
        $summary = DailyAttendance::where('date', $date)
            ->selectRaw('
                hours_status,
                COUNT(*) as total,
                AVG(actual_work_hours) as avg_hours
            ')
            ->groupBy('hours_status')
            ->get()
            ->keyBy('hours_status');

        // Anomali yang masih OPEN
        $openAnomalies = ShiftAnomaly::with('employee')
            ->where('anomaly_date', $date)
            ->where('resolution_status', 'OPEN')
            ->orderByRaw("FIELD(severity, 'HIGH', 'MEDIUM', 'LOW')")
            ->get();

        // Karyawan tanpa clock_out (MISSING_OUT)
        $missingOut = DailyAttendance::with('employee')
            ->where('date', $date)
            ->whereNotNull('clock_in_datetime')
            ->whereNull('clock_out_datetime')
            ->get();

        // Pengajuan lembur pending
        $pendingOT = OvertimeRequest::with('employee')
            ->where('work_date', $date)
            ->where('status', 'PENDING')
            ->get();

        // Deteksi tap yang diblokir (EARLY_CHECKIN)
        $blockedTaps = NextDaySchedule::with('employee')
            ->where('reference_date', Carbon::parse($date)->subDay()->toDateString())
            ->where('blocked_tap_detected', true)
            ->get();

        return view('hr.attendance-dashboard.index', compact(
            'date', 'summary', 'openAnomalies', 'missingOut', 'pendingOT', 'blockedTaps'
        ));
    }

    /**
     * Detail kehadiran satu karyawan satu hari, termasuk semua break events.
     */
    public function show(Request $request, int $employeeId, string $date)
    {
        $attendance = DailyAttendance::with(['employee', 'shift'])
            ->where('employee_id', $employeeId)
            ->where('date', $date)
            ->firstOrFail();

        $breakEvents = BreakEvent::where('daily_attendance_id', $attendance->id)
            ->orderBy('break_out')
            ->get();

        $anomalies = ShiftAnomaly::where('employee_id', $employeeId)
            ->where('anomaly_date', $date)
            ->get();

        return view('hr.attendance-dashboard.show', compact(
            'attendance', 'breakEvents', 'anomalies'
        ));
    }

    /**
     * HR Override: ubah exception_type dan recalculate.
     */
    public function applyOverride(Request $request, DailyAttendance $attendance)
    {
        $request->validate([
            'exception_type'    => 'required|in:BUSINESS_TRIP,MEDICAL,APPROVED_ERRAND,OTHER',
            'adjustment_mins'   => 'required|integer|min:0|max:480',
            'override_reason'   => 'required|string|max:500',
        ]);

        $this->overrideService->applyOverride(
            $attendance,
            $request->exception_type,
            auth()->id(),
            $request->adjustment_mins
        );

        return back()->with('success',
            "Override berhasil diterapkan. Actual work hours direkalkukasi."
        );
    }

    /**
     * Batalkan override.
     */
    public function revertOverride(DailyAttendance $attendance)
    {
        $this->overrideService->revertOverride($attendance);

        return back()->with('success', 'Override dibatalkan. Data dikembalikan ke kalkulasi asli.');
    }

    /**
     * Resolve anomali.
     */
    public function resolveAnomaly(Request $request, ShiftAnomaly $anomaly)
    {
        $request->validate([
            'resolution_status' => 'required|in:ACKNOWLEDGED,RESOLVED,DISMISSED',
            'resolution_note'   => 'nullable|string|max:500',
        ]);

        $anomaly->update([
            'resolution_status' => $request->resolution_status,
            'resolution_note'   => $request->resolution_note,
            'resolved_by'       => auth()->id(),
            'resolved_at'       => now(),
        ]);

        return back()->with('success', 'Anomali telah di-resolve.');
    }

    /**
     * Approve/reject overtime request.
     */
    public function processOvertimeRequest(Request $request, OvertimeRequest $otRequest)
    {
        $request->validate([
            'action'            => 'required|in:APPROVE,REJECT',
            'approved_ot_mins'  => 'required_if:action,APPROVE|integer|min:0',
            'rejection_reason'  => 'required_if:action,REJECT|string|max:500',
        ]);

        if ($request->action === 'APPROVE') {
            $otRequest->update([
                'status'          => 'APPROVED',
                'approved_by'     => auth()->id(),
                'approved_at'     => now(),
                'approved_ot_mins'=> $request->approved_ot_mins,
            ]);

            // Update daily_attendance
            if ($otRequest->daily_attendance_id) {
                DailyAttendance::where('id', $otRequest->daily_attendance_id)
                    ->update([
                        'overtime_minutes' => $request->approved_ot_mins,
                        'hours_status'     => 'OT',
                    ]);
            }
        } else {
            $otRequest->update([
                'status'           => 'REJECTED',
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);
        }

        return back()->with('success', "Overtime request telah di-{$request->action}.");
    }

    /**
     * API endpoint: cek apakah karyawan boleh tap IN saat ini.
     * Dipanggil dari level aplikasi untuk memblokir tap terlalu awal.
     */
    public function checkEarlyCheckin(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $yesterday = now()->subDay()->toDateString();

        $schedule = NextDaySchedule::where('employee_id', $request->employee_id)
            ->where('reference_date', $yesterday)
            ->first();

        if (! $schedule) {
            return response()->json(['allowed' => true]);
        }

        $allowed = now()->gte($schedule->earliest_allowed_start);

        return response()->json([
            'allowed'          => $allowed,
            'earliest_allowed' => $schedule->earliest_allowed_start->toDateTimeString(),
            'remaining_mins'   => $allowed ? 0 : now()->diffInMinutes($schedule->earliest_allowed_start),
        ]);
    }
}
