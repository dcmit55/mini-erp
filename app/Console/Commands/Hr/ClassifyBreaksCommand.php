<?php

namespace App\Console\Commands\Hr;

use App\Models\Hr\BreakEvent;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\EmployeeWorkPolicy;
use App\Models\Hr\Shift;
use App\Models\Hr\ShiftAnomaly;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * TAHAP 3: Break/Leave Classifier
 *
 * Untuk setiap break_event:
 *   - Jika gap <= break_max_duration_mins (default 90 mnt) → BREAK
 *   - Jika gap > 90 mnt → LONG_ABSENCE + anomali
 *   - Cek apakah tap OUT dalam break_window shift → tandai within_break_window
 *
 * Setelah klasifikasi, update daily_attendances:
 *   - total_break_mins
 *   - hours_status (FULL/SHORT/INCOMPLETE/OT)
 *   - early_leave_minutes
 *
 * Jalankan: php artisan hr:classify-breaks --date=2026-03-10
 */
class ClassifyBreaksCommand extends Command
{
    protected $signature = 'hr:classify-breaks
                            {--date= : Tanggal tertentu (Y-m-d)}
                            {--date-from= : Tanggal mulai}
                            {--date-to= : Tanggal akhir}
                            {--employee= : Employee ID tertentu}';

    protected $description = 'Klasifikasi break events dan update ringkasan daily_attendances';

    public function handle(): int
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange();

        $query = DailyAttendance::with(['employee.workPolicy'])
            ->whereNotNull('clock_in_datetime')
            ->whereNotNull('clock_out_datetime')
            ->whereBetween('date', [$dateFrom, $dateTo]);

        if ($empId = $this->option('employee')) {
            $query->where('employee_id', $empId);
        }

        $attendances = $query->get();
        $this->info("Mengklasifikasi {$attendances->count()} rekord kehadiran...");
        $bar = $this->output->createProgressBar($attendances->count());

        foreach ($attendances as $attendance) {
            $this->classifyAttendance($attendance);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Klasifikasi selesai.');

        return self::SUCCESS;
    }

    private function classifyAttendance(DailyAttendance $attendance): void
    {
        $shift    = $this->resolveShift($attendance);
        $breakMax = $shift?->break_max_duration_mins ?? 90;

        $breakEvents = BreakEvent::where('daily_attendance_id', $attendance->id)->get();

        $totalBreakMins = 0;

        foreach ($breakEvents as $event) {
            // Cek apakah dalam break window shift
            $withinWindow = $shift
                ? $shift->isWithinBreakWindow($event->break_out)
                : false;

            if ($event->break_in === null) {
                // Tidak ada tap IN setelah OUT ini → UNMATCHED
                $event->classification     = 'UNMATCHED';
                $event->within_break_window = $withinWindow;
                $event->flagged            = true;
                $event->flag_reason        = 'Tidak ada tap IN yang mengikuti tap OUT ini';
                $event->save();
                continue;
            }

            $gapMins = $event->break_out->diffInMinutes($event->break_in);

            if ($gapMins <= $breakMax || $withinWindow) {
                // Istirahat normal
                $event->classification      = 'BREAK';
                $event->within_break_window = $withinWindow;
                $event->flagged             = false;
                $totalBreakMins += $gapMins;
            } else {
                // Absen panjang > breakMax menit
                $event->classification      = 'LONG_ABSENCE';
                $event->within_break_window = $withinWindow;
                $event->flagged             = true;
                $event->flag_reason         = "Gap {$gapMins} menit melebihi batas {$breakMax} menit";

                ShiftAnomaly::log($attendance->employee_id, $attendance->date, 'LONG_ABSENCE', [
                    'break_out'   => $event->break_out->toDateTimeString(),
                    'break_in'    => $event->break_in->toDateTimeString(),
                    'gap_mins'    => $gapMins,
                    'threshold'   => $breakMax,
                ]);

                // Tetap hitung sebagai break untuk actual_work_hours
                // (HR bisa override nanti jika memang ada alasan)
                $totalBreakMins += $gapMins;
            }

            $event->save();
        }

        // Hitung early leave
        $earlyLeaveMins = 0;
        if ($shift) {
            $shiftEndToday = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $shift->shift_end);

            // Handle overnight shift
            if ($shift->is_overnight && $shiftEndToday->lt(Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $shift->shift_start))) {
                $shiftEndToday->addDay();
            }

            if ($attendance->clock_out_datetime->lt($shiftEndToday)) {
                $earlyLeaveMins = $attendance->clock_out_datetime->diffInMinutes($shiftEndToday);
            }
        }

        // Hitung actual_work_hours dari generated column (perlu refresh)
        $actualHours = $this->calculateActualHours($attendance, $totalBreakMins);

        // Tentukan hours_status
        $hoursStatus = $shift
            ? $shift->resolveHoursStatus($actualHours)
            : $this->defaultHoursStatus($actualHours);

        // Cek overtime (jika ada OvertimeRequest yang approved)
        $approvedOtMins = DB::table('overtime_requests')
            ->where('employee_id', $attendance->employee_id)
            ->where('work_date', $attendance->date)
            ->where('status', 'APPROVED')
            ->sum('approved_ot_mins');

        if ($approvedOtMins > 0 && $hoursStatus !== 'OT') {
            $hoursStatus = 'OT';
        }

        // Update daily_attendance
        $attendance->total_break_mins    = $totalBreakMins;
        $attendance->hours_status        = $hoursStatus;
        $attendance->early_leave_minutes = $earlyLeaveMins;
        $attendance->overtime_minutes    = max(0, (int)(($actualHours - ($shift?->ot_threshold_hours ?? 9)) * 60));
        $attendance->saveQuietly();

        // Catat anomali early leave
        if ($earlyLeaveMins > 0) {
            // Cek apakah sudah ada anomali EARLY_LEAVE untuk hari ini
            $existingAnomaly = \App\Models\Hr\ShiftAnomaly::where('employee_id', $attendance->employee_id)
                ->where('anomaly_date', $attendance->date)
                ->where('anomaly_type', 'EARLY_LEAVE')
                ->first();

            if (! $existingAnomaly) {
                ShiftAnomaly::log($attendance->employee_id, $attendance->date, 'EARLY_LEAVE', [
                    'clock_out'        => $attendance->clock_out_datetime->toDateTimeString(),
                    'shift_end'        => $shift?->shift_end,
                    'early_leave_mins' => $earlyLeaveMins,
                ]);
            }
        }

        // Catat NO_BREAKS jika shift panjang dan tidak ada istirahat
        if ($shift && $shift->expected_hours >= 6 && $totalBreakMins === 0) {
            $existing = \App\Models\Hr\ShiftAnomaly::where('employee_id', $attendance->employee_id)
                ->where('anomaly_date', $attendance->date)
                ->where('anomaly_type', 'NO_BREAKS')
                ->first();

            if (! $existing) {
                ShiftAnomaly::log($attendance->employee_id, $attendance->date, 'NO_BREAKS', [
                    'actual_hours'    => $actualHours,
                    'expected_hours'  => $shift->expected_hours,
                ]);
            }
        }
    }

    /**
     * Tentukan shift yang berlaku untuk karyawan pada hari tertentu.
     * Prioritas: daily_attendances.shift_id → employee_work_policies.shift_id → null
     */
    private function resolveShift(DailyAttendance $attendance): ?Shift
    {
        if ($attendance->shift_id) {
            return Shift::find($attendance->shift_id);
        }

        $policy = EmployeeWorkPolicy::where('employee_id', $attendance->employee_id)->first();

        if ($policy && isset($policy->shift_id)) {
            return Shift::find($policy->shift_id);
        }

        return null;
    }

    private function calculateActualHours(DailyAttendance $attendance, int $totalBreakMins): float
    {
        if (! $attendance->clock_in_datetime || ! $attendance->clock_out_datetime) {
            return 0;
        }

        $grossMins = $attendance->clock_in_datetime->diffInMinutes($attendance->clock_out_datetime);
        return round(($grossMins - $totalBreakMins) / 60, 2);
    }

    private function defaultHoursStatus(float $hours): string
    {
        if ($hours >= 9)  return 'OT';
        if ($hours >= 8)  return 'FULL';
        if ($hours >= 7)  return 'SHORT';
        return 'INCOMPLETE';
    }

    private function resolveDateRange(): array
    {
        if ($date = $this->option('date')) {
            return [$date, $date];
        }
        return [
            $this->option('date-from') ?? now()->toDateString(),
            $this->option('date-to')   ?? now()->toDateString(),
        ];
    }
}
