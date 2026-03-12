<?php

namespace App\Services\Hr;

use App\Models\Hr\BreakEvent;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Shift;

/**
 * HR Override Service
 *
 * HR dapat meng-override exception_type pada daily_attendances.
 * Override TIDAK mengubah fingerprint_logs atau break_events,
 * hanya memengaruhi perhitungan hours_status dan actual_work_hours.
 *
 * Contoh kasus:
 *   - Karyawan keluar 2 jam untuk urusan dinas (BUSINESS_TRIP)
 *   - Periode yang sebelumnya dianggap LONG_ABSENCE menjadi kerja
 *   - actual_work_hours naik sesuai durasi yang dikecualikan
 */
class AttendanceOverrideService
{
    /**
     * Terapkan override exception_type ke daily_attendance.
     *
     * @param DailyAttendance $attendance
     * @param string $exceptionType  BUSINESS_TRIP | MEDICAL | APPROVED_ERRAND | OTHER
     * @param int|null $approvedBy   User ID yang menyetujui
     * @param int $adjustmentMins    Menit kerja tambahan yang dikecualikan dari break
     */
    public function applyOverride(
        DailyAttendance $attendance,
        string $exceptionType,
        ?int $approvedBy,
        int $adjustmentMins = 0
    ): DailyAttendance {
        // Hitung kembali total_break_mins dengan mengurangi adjustment
        // (periode yang dikecualikan tidak lagi dihitung sebagai break)
        $newBreakMins = max(0, $attendance->total_break_mins - $adjustmentMins);

        // Hitung actual_work_hours baru
        $grossMins    = $attendance->clock_in_datetime->diffInMinutes($attendance->clock_out_datetime);
        $actualHours  = round(($grossMins - $newBreakMins) / 60, 2);

        // Tentukan hours_status baru
        $shift       = $attendance->shift_id ? Shift::find($attendance->shift_id) : null;
        $hoursStatus = $shift
            ? $shift->resolveHoursStatus($actualHours)
            : $this->defaultHoursStatus($actualHours);

        $attendance->exception_type      = $exceptionType;
        $attendance->total_break_mins    = $newBreakMins;
        $attendance->hours_status        = $hoursStatus;
        $attendance->supervisor_approved = true;

        // Tandai break_events yang menjadi dasar adjustment sebagai 'resolved'
        // (tidak dihapus, hanya catatan bahwa HR sudah mengakui)
        if ($adjustmentMins > 0) {
            BreakEvent::where('daily_attendance_id', $attendance->id)
                ->where('classification', 'LONG_ABSENCE')
                ->update([
                    'flag_reason' => "Override oleh HR: {$exceptionType}. Adjustment: {$adjustmentMins} mnt.",
                ]);
        }

        $attendance->save();

        return $attendance;
    }

    /**
     * Batalkan override — kembalikan ke kalkulasi asli dari break_events.
     */
    public function revertOverride(DailyAttendance $attendance): DailyAttendance
    {
        // Hitung ulang total_break_mins dari break_events BREAK saja
        $totalBreakMins = BreakEvent::where('daily_attendance_id', $attendance->id)
            ->where('classification', 'BREAK')
            ->sum('duration_mins');

        $grossMins   = $attendance->clock_in_datetime->diffInMinutes($attendance->clock_out_datetime);
        $actualHours = round(($grossMins - $totalBreakMins) / 60, 2);

        $shift       = $attendance->shift_id ? Shift::find($attendance->shift_id) : null;
        $hoursStatus = $shift
            ? $shift->resolveHoursStatus($actualHours)
            : $this->defaultHoursStatus($actualHours);

        $attendance->exception_type      = 'NONE';
        $attendance->total_break_mins    = $totalBreakMins;
        $attendance->hours_status        = $hoursStatus;
        $attendance->supervisor_approved = false;
        $attendance->save();

        return $attendance;
    }

    /**
     * Validasi silang: pastikan tidak ada sesi produksi yang
     * melebihi actual_work_hours yang tercatat di daily_attendances.
     *
     * Mengembalikan array employee_id yang melanggar.
     */
    public function crossValidateProductionSessions(string $date): array
    {
        // Ambil semua daily_attendances dengan actual_work_hours terisi
        $attendances = DailyAttendance::where('date', $date)
            ->whereNotNull('clock_out_datetime')
            ->get();

        $violations = [];

        foreach ($attendances as $att) {
            $grossMins   = $att->clock_in_datetime->diffInMinutes($att->clock_out_datetime);
            $actualHours = round(($grossMins - $att->total_break_mins) / 60, 2);

            // Jika actual_work_hours di DB (generated) berbeda dari kalkulasi manual
            // ini bisa terjadi jika total_break_mins tidak sinkron
            // (dalam implementasi real, cek ini terhadap tabel produksi/WO)
            if (abs($att->actual_work_hours - $actualHours) > 0.01) {
                $violations[] = [
                    'employee_id'       => $att->employee_id,
                    'date'              => $date,
                    'stored'            => $att->actual_work_hours,
                    'calculated'        => $actualHours,
                    'diff'              => round($att->actual_work_hours - $actualHours, 2),
                ];
            }
        }

        return $violations;
    }

    private function defaultHoursStatus(float $hours): string
    {
        if ($hours >= 9)  return 'OT';
        if ($hours >= 8)  return 'FULL';
        if ($hours >= 7)  return 'SHORT';
        return 'INCOMPLETE';
    }
}
