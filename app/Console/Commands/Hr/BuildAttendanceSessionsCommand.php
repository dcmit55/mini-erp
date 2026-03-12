<?php

namespace App\Console\Commands\Hr;

use App\Models\FingerprintLog;
use App\Models\Hr\BreakEvent;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use App\Models\Hr\ShiftAnomaly;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * TAHAP 2: Session Builder
 *
 * Mengambil fingerprint_logs yang sudah diparsing untuk setiap karyawan
 * per hari, lalu membangun sesi kehadiran:
 *   - clock_in_datetime  = tap IN pertama
 *   - clock_out_datetime = tap OUT terakhir
 *   - break_events       = semua sesi IN-OUT di antaranya
 *
 * Jalankan: php artisan hr:build-sessions --date=2026-03-10
 *           php artisan hr:build-sessions --date-from=2026-03-01 --date-to=2026-03-10
 */
class BuildAttendanceSessionsCommand extends Command
{
    protected $signature = 'hr:build-sessions
                            {--date= : Proses tanggal tertentu (Y-m-d)}
                            {--date-from= : Tanggal mulai range}
                            {--date-to= : Tanggal akhir range}
                            {--employee= : Proses employee_id tertentu saja}
                            {--force : Timpa data yang sudah ada}';

    protected $description = 'Bangun sesi kehadiran dari fingerprint_logs yang sudah diparsing';

    public function handle(): int
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange();
        $employeeId = $this->option('employee');
        $force = $this->option('force');

        $this->info("Memproses sesi {$dateFrom} s/d {$dateTo}" . ($employeeId ? " untuk employee #{$employeeId}" : ""));

        // Ambil kombinasi employee_id + tanggal yang punya log terparsing
        $query = FingerprintLog::query()
            ->whereNotNull('employee_id')
            ->whereNotNull('direction')
            ->whereNotNull('parsed_at')
            ->whereBetween(DB::raw('DATE(event_time)'), [$dateFrom, $dateTo])
            ->selectRaw('employee_id, DATE(event_time) as work_date')
            ->groupBy('employee_id', DB::raw('DATE(event_time)'))
            ->orderBy('work_date')
            ->orderBy('employee_id');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $pairs = $query->get();
        $this->info("Ditemukan {$pairs->count()} kombinasi karyawan-tanggal.");
        $bar = $this->output->createProgressBar($pairs->count());

        foreach ($pairs as $pair) {
            $this->processEmployeeDay($pair->employee_id, $pair->work_date, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Build sessions selesai.');

        return self::SUCCESS;
    }

    private function processEmployeeDay(int $employeeId, string $workDate, bool $force): void
    {
        // Ambil semua tap pada hari tersebut, urut by event_time
        $taps = FingerprintLog::where('employee_id', $employeeId)
            ->whereDate('event_time', $workDate)
            ->whereNotNull('direction')
            ->orderBy('event_time')
            ->get();

        if ($taps->isEmpty()) return;

        // Deteksi tap duplikat (dalam 1 menit)
        $this->detectDuplicateTaps($employeeId, $workDate, $taps);

        // Deduplikasi: hapus tap dalam window 1 menit yang sama arahnya
        $taps = $this->deduplicateTaps($taps);

        // Ambil tap IN pertama dan OUT terakhir sebagai sesi utama
        $firstIn  = $taps->where('direction', 'IN')->first();
        $lastOut  = $taps->where('direction', 'OUT')->last();

        if (! $firstIn) {
            // Tidak ada tap IN sama sekali
            ShiftAnomaly::log($employeeId, $workDate, 'MISSING_OUT', [
                'note' => 'Tidak ada tap IN ditemukan',
                'taps_count' => $taps->count(),
            ]);
            return;
        }

        // Cek apakah daily_attendance sudah ada
        $existing = DailyAttendance::where('employee_id', $employeeId)
            ->where('date', $workDate)
            ->first();

        if ($existing && ! $force) {
            // Skip jika sudah ada dan tidak force
            return;
        }

        DB::transaction(function () use ($employeeId, $workDate, $taps, $firstIn, $lastOut, $existing) {
            // Buat/update daily_attendance
            $attendance = $existing ?? new DailyAttendance();
            $attendance->employee_id       = $employeeId;
            $attendance->date              = $workDate;
            $attendance->clock_in_datetime  = $firstIn->event_time;
            $attendance->clock_out_datetime = $lastOut?->event_time;
            $attendance->clock_in  = $firstIn->event_time->format('H:i:s');
            $attendance->clock_out = $lastOut?->event_time->format('H:i:s');

            // Hitung actual_work_hours (tidak pakai GENERATED column)
            if ($lastOut) {
                $grossMins = $firstIn->event_time->diffInMinutes($lastOut->event_time);
                $breakMins = (int) ($attendance->total_break_mins ?? 0);
                $attendance->actual_work_hours = round(max(0, $grossMins - $breakMins) / 60, 2);
            } else {
                $attendance->actual_work_hours = null;
            }

            if (! $attendance->exists) {
                $attendance->status = 'Present';
                $attendance->created_by = 1; // system user
            }
            $attendance->updated_by = 1;
            $attendance->save();

            if (! $lastOut) {
                ShiftAnomaly::log($employeeId, $workDate, 'MISSING_OUT', [
                    'clock_in' => $firstIn->event_time->toDateTimeString(),
                ]);
                return;
            }

            // Bangun break events: semua pasangan IN-OUT di antara firstIn dan lastOut
            $this->buildBreakEvents($attendance, $taps, $firstIn, $lastOut);
        });
    }

    /**
     * Pasangkan tap IN-OUT untuk membentuk break events.
     *
     * Algoritma:
     * 1. Mulai dari tap kedua (skip firstIn)
     * 2. Setiap tap OUT yang diikuti tap IN = calon break event
     * 3. Tap OUT terakhir (lastOut) diabaikan karena sudah jadi clock_out
     */
    private function buildBreakEvents(
        DailyAttendance $attendance,
        $taps,
        $firstIn,
        $lastOut
    ): void {
        // Hapus break events lama untuk hari ini
        BreakEvent::where('daily_attendance_id', $attendance->id)->delete();

        // Filter tap yang berada di antara firstIn dan lastOut
        $middleTaps = $taps->filter(function ($tap) use ($firstIn, $lastOut) {
            return $tap->event_time->gt($firstIn->event_time)
                && $tap->event_time->lt($lastOut->event_time);
        })->values();

        // Pasangkan OUT → IN secara berurutan
        $i = 0;
        while ($i < $middleTaps->count()) {
            $tap = $middleTaps[$i];

            if ($tap->direction === 'OUT') {
                // Cari IN berikutnya
                $nextIn = null;
                for ($j = $i + 1; $j < $middleTaps->count(); $j++) {
                    if ($middleTaps[$j]->direction === 'IN') {
                        $nextIn = $middleTaps[$j];
                        break;
                    }
                }

                BreakEvent::create([
                    'daily_attendance_id' => $attendance->id,
                    'employee_id'         => $attendance->employee_id,
                    'work_date'           => $attendance->date,
                    'break_out'           => $tap->event_time,
                    'break_in'            => $nextIn?->event_time,
                    'classification'      => 'BREAK', // akan diupdate oleh ClassifyBreaksCommand
                ]);

                // Lompat ke setelah nextIn
                $i = $nextIn ? ($j + 1) : ($i + 1);
            } else {
                $i++;
            }
        }
    }

    private function detectDuplicateTaps(int $employeeId, string $workDate, $taps): void
    {
        $prev = null;
        foreach ($taps as $tap) {
            if ($prev && $tap->event_time->diffInSeconds($prev->event_time) <= 60
                && $tap->direction === $prev->direction) {
                ShiftAnomaly::log($employeeId, $workDate, 'DUPLICATE_TAP', [
                    'tap1' => $prev->event_time->toDateTimeString(),
                    'tap2' => $tap->event_time->toDateTimeString(),
                    'direction' => $tap->direction,
                ]);
            }
            $prev = $tap;
        }
    }

    /**
     * Hapus tap duplikat: jika arah sama dan selisih <= 60 detik, ambil yang pertama.
     */
    private function deduplicateTaps($taps)
    {
        $result = collect();
        $prev   = null;

        foreach ($taps as $tap) {
            if ($prev
                && $tap->direction === $prev->direction
                && $tap->event_time->diffInSeconds($prev->event_time) <= 60) {
                continue; // skip duplikat
            }
            $result->push($tap);
            $prev = $tap;
        }

        return $result;
    }

    private function resolveDateRange(): array
    {
        if ($date = $this->option('date')) {
            return [$date, $date];
        }

        $from = $this->option('date-from') ?? now()->toDateString();
        $to   = $this->option('date-to')   ?? now()->toDateString();

        return [$from, $to];
    }
}
