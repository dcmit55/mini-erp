<?php

namespace App\Console\Commands\Hr;

use App\Models\Hr\DailyAttendance;
use App\Models\Hr\EmployeeWorkPolicy;
use App\Models\Hr\NextDaySchedule;
use App\Models\Hr\Shift;
use App\Models\Hr\ShiftAnomaly;
use App\Models\FingerprintLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * TAHAP 4: Hitung jadwal hari berikutnya
 *
 * Aturan: karyawan tidak boleh tap IN sebelum 10 jam setelah clock_out terakhir.
 * Jika ada tap IN yang terdeteksi lebih awal, catat sebagai anomali EARLY_CHECKIN.
 *
 * Jalankan: php artisan hr:calc-next-schedule --date=2026-03-10
 */
class CalculateNextDayScheduleCommand extends Command
{
    protected $signature = 'hr:calc-next-schedule
                            {--date= : Tanggal sesi referensi (Y-m-d)}
                            {--date-from= : Tanggal mulai}
                            {--date-to= : Tanggal akhir}';

    protected $description = 'Hitung earliest_allowed_start untuk sesi hari berikutnya';

    public function handle(): int
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange();

        $attendances = DailyAttendance::whereNotNull('clock_out_datetime')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        $this->info("Memproses {$attendances->count()} rekord...");

        foreach ($attendances as $attendance) {
            $this->processAttendance($attendance);
        }

        $this->info('Kalkulasi next-day schedule selesai.');

        return self::SUCCESS;
    }

    private function processAttendance(DailyAttendance $attendance): void
    {
        $shift = $this->resolveShift($attendance);
        $restMins = $shift?->min_rest_between_shifts_mins ?? 600; // default 10 jam

        $earliestStart = $attendance->clock_out_datetime->copy()->addMinutes($restMins);

        // Upsert next_day_schedule
        $schedule = NextDaySchedule::updateOrCreate(
            [
                'employee_id'    => $attendance->employee_id,
                'reference_date' => $attendance->date,
            ],
            [
                'actual_clock_out'       => $attendance->clock_out_datetime,
                'earliest_allowed_start' => $earliestStart,
            ]
        );

        // Cek apakah ada tap IN keesokan harinya sebelum earliest_allowed_start
        $nextDate = Carbon::parse($attendance->date)->addDay()->toDateString();

        $earlyTap = FingerprintLog::where('employee_id', $attendance->employee_id)
            ->where('direction', 'IN')
            ->whereDate('event_time', $nextDate)
            ->where('event_time', '<', $earliestStart)
            ->first();

        if ($earlyTap) {
            // Tandai bahwa ada tap terlalu awal
            $schedule->blocked_tap_detected = true;
            $schedule->save();

            ShiftAnomaly::log($attendance->employee_id, $nextDate, 'EARLY_CHECKIN', [
                'tap_time'        => $earlyTap->event_time->toDateTimeString(),
                'earliest_allowed'=> $earliestStart->toDateTimeString(),
                'diff_mins'       => $earlyTap->event_time->diffInMinutes($earliestStart),
            ]);
        }
    }

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

    private function resolveDateRange(): array
    {
        if ($date = $this->option('date')) {
            return [$date, $date];
        }
        return [
            $this->option('date-from') ?? now()->subDay()->toDateString(),
            $this->option('date-to')   ?? now()->subDay()->toDateString(),
        ];
    }
}
