<?php

namespace App\Console\Commands\Hr;

use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use App\Models\Hr\SessionShift;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Backfill session_shift_id yang kosong di daily_attendances.
 *
 * Aturan:
 *  - Costume   → shift dengan type_of_shift = 'c8'
 *  - Lainnya   → shift dengan type_of_shift = 'a9'
 *
 * Jalankan: php artisan hr:backfill-session-shift [--date=2026-03-26] [--days=7] [--dry-run]
 */
class BackfillSessionShiftCommand extends Command
{
    protected $signature = 'hr:backfill-session-shift
                            {--date=   : Tanggal mulai (Y-m-d), default hari ini}
                            {--days=1  : Jumlah hari ke belakang}
                            {--dry-run : Tampilkan perubahan tanpa simpan}';

    protected $description = 'Isi session_shift_id yang kosong di daily_attendances berdasarkan departemen karyawan';

    public function handle(): int
    {
        $date   = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $days   = max(1, (int) $this->option('days'));
        $dryRun = $this->option('dry-run');

        // Load shift defaults sekali saja
        $shiftA9 = SessionShift::where('type_of_shift', 'a9')->where('is_active', true)->first();
        $shiftC8 = SessionShift::where('type_of_shift', 'c8')->where('is_active', true)->first();

        if (! $shiftA9) {
            $this->error('Shift a9 tidak ditemukan di tabel session_shifts atau tidak aktif!');
            return self::FAILURE;
        }
        if (! $shiftC8) {
            $this->error('Shift c8 tidak ditemukan di tabel session_shifts atau tidak aktif!');
            return self::FAILURE;
        }

        $this->line('Shift a9 ID: <info>' . $shiftA9->id . '</info>  |  Shift c8 ID: <info>' . $shiftC8->id . '</info>');
        $dryRun && $this->warn('[DRY-RUN] Tidak ada data yang disimpan.');
        $this->newLine();

        $totalUpdated = 0;

        for ($i = 0; $i < $days; $i++) {
            $checkDate = $date->copy()->subDays($i)->format('Y-m-d');

            $records = DailyAttendance::whereDate('date', $checkDate)
                ->whereNull('session_shift_id')
                ->with('employee.department')
                ->get();

            $this->line("  <comment>{$checkDate}</comment> → {$records->count()} record tanpa shift");

            foreach ($records as $record) {
                $employee  = $record->employee;
                $deptName  = strtolower($employee?->department?->name ?? '');
                $shift     = str_contains($deptName, 'costume') ? $shiftC8 : $shiftA9;
                $empName   = $employee?->name ?? '?';
                $deptLabel = $deptName ?: 'dept?';

                $this->line("    → {$empName} ({$deptLabel}) ← <info>{$shift->type_of_shift}</info>");

                if (! $dryRun) {
                    $record->session_shift_id = $shift->id;
                    $record->saveQuietly();
                }

                $totalUpdated++;
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY-RUN selesai. {$totalUpdated} record akan diupdate jika dijalankan tanpa --dry-run.");
        } else {
            $this->info("Selesai. {$totalUpdated} record diupdate.");
        }

        return self::SUCCESS;
    }
}
