<?php

namespace App\Console\Commands\Hr;

use App\Models\Hr\DailyAttendance;
use App\Models\Hr\ShiftAnomaly;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * TAHAP 5: Deteksi Anomali Shift (7 Aturan)
 *
 * Dijalankan sebagai cron job harian, setelah semua tahap sebelumnya selesai.
 *
 * Aturan yang dideteksi:
 *   1. SHORT_HOURS    - actual_work_hours < 7 jam
 *   2. NO_BREAKS      - tidak ada istirahat pada shift >= 6 jam (sudah di ClassifyBreaks)
 *   3. LONG_ABSENCE   - gap > 90 menit (sudah di ClassifyBreaks)
 *   4. EARLY_LEAVE    - clock_out sebelum shift_end (sudah di ClassifyBreaks)
 *   5. PATTERN        - actual_work_hours = 8.00 persis selama 5+ hari berturut-turut
 *   6. EARLY_CHECKIN  - tap IN < 10 jam setelah clock_out (sudah di CalcNextSchedule)
 *   7. MISSING_OUT    - tidak ada clock_out (sudah di BuildSessions)
 *
 * Command ini fokus pada aturan 1 dan 5 yang memerlukan agregasi data.
 *
 * Jalankan: php artisan hr:detect-anomalies --date=2026-03-10
 */
class DetectShiftAnomaliesCommand extends Command
{
    protected $signature = 'hr:detect-anomalies
                            {--date= : Tanggal target (default: kemarin)}
                            {--rule= : Jalankan aturan tertentu saja: SHORT_HOURS|PATTERN}';

    protected $description = 'Deteksi anomali shift: SHORT_HOURS, PATTERN';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->subDay()->toDateString();
        $rule = $this->option('rule');

        $this->info("Mendeteksi anomali untuk tanggal: {$date}");

        if (! $rule || $rule === 'SHORT_HOURS') {
            $this->detectShortHours($date);
        }

        if (! $rule || $rule === 'PATTERN') {
            $this->detectPerfectPattern($date);
        }

        $this->info('Deteksi anomali selesai.');

        return self::SUCCESS;
    }

    /**
     * Aturan 1: SHORT_HOURS
     * actual_work_hours < 7.0 (kecuali sudah ada exception_type atau LEAVE)
     */
    private function detectShortHours(string $date): void
    {
        $records = DailyAttendance::where('date', $date)
            ->whereNotNull('clock_out_datetime')
            ->where('hours_status', 'INCOMPLETE')
            ->where('exception_type', 'NONE')
            ->whereNotIn('status', ['Sick Leave', 'Annual Leave', 'Excused'])
            ->get();

        $count = 0;
        foreach ($records as $record) {
            $actualHours = $this->getActualHours($record);

            // Cek sudah ada anomali hari ini
            $exists = ShiftAnomaly::where('employee_id', $record->employee_id)
                ->where('anomaly_date', $date)
                ->where('anomaly_type', 'SHORT_HOURS')
                ->exists();

            if (! $exists) {
                ShiftAnomaly::log($record->employee_id, $date, 'SHORT_HOURS', [
                    'actual_hours'  => $actualHours,
                    'threshold'     => 7.0,
                    'hours_status'  => $record->hours_status,
                    'total_break_mins' => $record->total_break_mins,
                ]);
                $count++;
            }
        }

        $this->line("SHORT_HOURS: {$count} anomali baru dicatat.");
    }

    /**
     * Aturan 5: PATTERN
     * actual_work_hours = tepat 8.00 selama 5+ hari berturut-turut.
     * Ini mengindikasikan kemungkinan manipulasi atau data yang tidak wajar.
     */
    private function detectPerfectPattern(string $date): void
    {
        // Ambil 10 hari ke belakang untuk mencari streak
        $endDate   = Carbon::parse($date);
        $startDate = $endDate->copy()->subDays(9); // 10 hari

        // Cari karyawan yang punya actual_work_hours = 8.00 pada hari ini
        $employees = DailyAttendance::where('date', $date)
            ->where('hours_status', 'FULL')
            ->whereRaw('actual_work_hours = 8.00')
            ->pluck('employee_id');

        $count = 0;

        foreach ($employees as $employeeId) {
            // Cek streak 5+ hari berturut-turut dengan nilai 8.00 persis
            $records = DailyAttendance::where('employee_id', $employeeId)
                ->whereBetween('date', [$startDate->toDateString(), $date])
                ->whereRaw('actual_work_hours = 8.00')
                ->orderBy('date')
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->toArray();

            $streak = $this->longestConsecutiveStreak($records, $date);

            if ($streak >= 5) {
                $exists = ShiftAnomaly::where('employee_id', $employeeId)
                    ->where('anomaly_date', $date)
                    ->where('anomaly_type', 'PATTERN')
                    ->exists();

                if (! $exists) {
                    ShiftAnomaly::log($employeeId, $date, 'PATTERN', [
                        'streak_days'  => $streak,
                        'dates'        => $records,
                        'description'  => "Jam kerja tepat 8.00 selama {$streak} hari berturut-turut",
                    ]);
                    $count++;
                }
            }
        }

        $this->line("PATTERN: {$count} anomali baru dicatat.");
    }

    /**
     * Hitung panjang streak berturut-turut yang berakhir pada $targetDate.
     */
    private function longestConsecutiveStreak(array $dates, string $targetDate): int
    {
        if (empty($dates)) return 0;

        $dateSet = array_flip($dates);
        $streak  = 0;
        $current = Carbon::parse($targetDate);

        while (isset($dateSet[$current->toDateString()])) {
            $streak++;
            $current->subDay();
            // Skip weekend jika perlu (opsional)
        }

        return $streak;
    }

    private function getActualHours(DailyAttendance $record): float
    {
        if (! $record->clock_in_datetime || ! $record->clock_out_datetime) return 0;
        $gross = $record->clock_in_datetime->diffInMinutes($record->clock_out_datetime);
        return round(($gross - $record->total_break_mins) / 60, 2);
    }
}
