<?php

namespace App\Console\Commands\Hr;

use App\Models\FingerprintLog;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Health-check: apakah data fingerprint masuk otomatis ke sistem?
 *
 * Cek:
 *  1. Fingerprint logs hari ini (raw tap dari mesin)
 *  2. Attendance logs hari ini (setelah rekonsiliasi)
 *  3. Daily attendances hari ini (setelah generate)
 *  4. Scheduler commands terdaftar
 *  5. Aktivitas webhook dari Laravel log
 *  6. Pipeline integrity (fingerprint_logs → attendance_logs → daily_attendances)
 *
 * Jalankan: php artisan hr:fingerprint-health [--date=2026-03-26] [--days=3] [--log-lines=50]
 */
class FingerprintHealthCheckCommand extends Command
{
    protected $signature = 'hr:fingerprint-health
                            {--date=   : Tanggal cek (Y-m-d), default hari ini}
                            {--days=1  : Jumlah hari ke belakang untuk dicek}
                            {--log-lines=30 : Jumlah baris log untuk dibaca}';

    protected $description = 'Cek apakah data fingerprint sudah masuk otomatis ke sistem (webhook + scheduler)';

    public function handle(): int
    {
        $date    = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $days    = max(1, (int) $this->option('days'));
        $logLines = max(10, (int) $this->option('log-lines'));

        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║         FINGERPRINT AUTO-SYNC HEALTH CHECK               ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->line('  Tanggal cek : <info>' . $date->format('d M Y') . '</info>  |  Rentang : <info>' . $days . ' hari</info>');
        $this->line('  Server time : <info>' . now()->format('d M Y H:i:s') . ' (' . config('app.timezone') . ')</info>');
        $this->newLine();

        $allPassed = true;

        // ── 1. Raw fingerprint logs ──────────────────────────────────────────
        $this->line('<fg=cyan;options=bold>1. RAW FINGERPRINT LOGS (fingerprint_logs)</>');

        for ($i = 0; $i < $days; $i++) {
            $checkDate = $date->copy()->subDays($i);
            $count     = FingerprintLog::whereDate('created_at', $checkDate)->count();
            $latest    = FingerprintLog::whereDate('created_at', $checkDate)->latest()->first();

            $status = $count > 0 ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $latestTime = $latest ? $latest->created_at->format('H:i:s') : '-';

            $this->line("  {$status} {$checkDate->format('d M Y')} : <info>{$count}</info> log masuk | terakhir: <comment>{$latestTime}</comment>");

            if ($count === 0 && $i === 0) {
                $allPassed = false;
            }
        }

        // ── 2. Attendance logs ───────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>2. ATTENDANCE LOGS (attendance_logs)</>');

        for ($i = 0; $i < $days; $i++) {
            $checkDate = $date->copy()->subDays($i);
            $count     = AttendanceLog::whereDate('date', $checkDate)->count();
            $withIn    = AttendanceLog::whereDate('date', $checkDate)->whereNotNull('clock_in')->count();
            $withOut   = AttendanceLog::whereDate('date', $checkDate)->whereNotNull('clock_out')->count();

            $status = $count > 0 ? '<fg=green>✓</>' : '<fg=yellow>⚠</>';
            $this->line("  {$status} {$checkDate->format('d M Y')} : <info>{$count}</info> karyawan | clock-in: <comment>{$withIn}</comment> | clock-out: <comment>{$withOut}</comment>");
        }

        // ── 3. Daily attendances ─────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>3. DAILY ATTENDANCES (daily_attendances)</>');

        for ($i = 0; $i < $days; $i++) {
            $checkDate    = $date->copy()->subDays($i);
            $count        = DailyAttendance::whereDate('date', $checkDate)->count();
            $withShift    = DailyAttendance::whereDate('date', $checkDate)->whereNotNull('session_shift_id')->count();
            $withoutShift = $count - $withShift;

            $status = $count > 0 ? '<fg=green>✓</>' : '<fg=yellow>⚠</>';
            $shiftWarn = $withoutShift > 0 ? " | <fg=yellow>⚠ {$withoutShift} tanpa shift (auto-break tidak akan jalan)</>" : '';
            $this->line("  {$status} {$checkDate->format('d M Y')} : <info>{$count}</info> record{$shiftWarn}");
        }

        // ── 4. Pipeline integrity ────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>4. PIPELINE INTEGRITY (hari ini)</>');

        $today         = $date->copy()->startOfDay();
        $rawCount      = FingerprintLog::whereDate('created_at', $today)->count();
        $attendCount   = AttendanceLog::whereDate('date', $today)->whereNotNull('clock_in')->count();
        $dailyCount    = DailyAttendance::whereDate('date', $today)->count();
        $totalEmployees = Employee::active()->count();

        $this->line("  fingerprint_logs  → <info>{$rawCount}</info> tap hari ini");
        $this->line("  attendance_logs   → <info>{$attendCount}</info> karyawan punya clock-in");
        $this->line("  daily_attendances → <info>{$dailyCount}</info> record | total karyawan aktif: <comment>{$totalEmployees}</comment>");

        if ($rawCount > 0 && $attendCount === 0) {
            $this->line('  <fg=red>✗ Raw log ada tapi attendance_logs kosong → rekonsiliasi gagal atau belum jalan!</>');
            $allPassed = false;
        } elseif ($attendCount > 0 && $dailyCount === 0) {
            $this->line('  <fg=red>✗ Attendance ada tapi daily_attendances kosong → DailyAttendanceService gagal!</>');
            $allPassed = false;
        } elseif ($rawCount === 0) {
            $this->line('  <fg=yellow>⚠ Belum ada tap hari ini — mesin belum kirim data atau webhook belum dikonfigurasi</>');
        } else {
            $this->line('  <fg=green>✓ Pipeline berjalan normal</>');
        }

        // ── 5. Scheduler commands ────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>5. SCHEDULED COMMANDS</>');

        $scheduledCommands = [
            'hr:sync-fingerspot'      => 'Pull data dari API Fingerspot (setiap 5 menit)',
            'timing:auto-break-pause' => 'Auto-freeze/unfreeze timing saat break (setiap menit)',
        ];

        foreach ($scheduledCommands as $cmd => $desc) {
            $this->line("  <fg=green>✓</> <comment>{$cmd}</comment> — {$desc}");
        }

        $this->line('');
        $this->line('  Cek crontab aktif di server:');
        $this->line('  <fg=yellow>  crontab -l | grep artisan</>');
        $this->line('  Harus ada: <fg=yellow>* * * * * php /path/to/artisan schedule:run</>');

        // ── 6. Webhook endpoint ──────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>6. WEBHOOK ENDPOINT</>');

        $webhookRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return str_contains($route->uri(), 'webhook/fingerprint');
        });

        if ($webhookRoutes->count() > 0) {
            foreach ($webhookRoutes as $route) {
                $methods = implode('|', $route->methods());
                $this->line("  <fg=green>✓</> [{$methods}] /" . $route->uri());
            }
        } else {
            $this->line('  <fg=red>✗ Route webhook tidak ditemukan!</>');
            $allPassed = false;
        }

        $appUrl = config('app.url');
        $this->line("  URL webhook: <info>{$appUrl}/api/webhook/fingerprint</info>");
        $this->line('  Pastikan URL ini bisa diakses dari jaringan mesin fingerspot.');

        // ── 7. Laravel log terbaru ───────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>7. AKTIVITAS TERBARU (Laravel Log)</>');

        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            $this->line('  <fg=yellow>⚠ File log tidak ditemukan: ' . $logPath . '</>');
        } else {
            $lines = $this->tailFile($logPath, $logLines * 5);
            $relevant = array_filter($lines, fn($l) =>
                str_contains($l, 'webhook') ||
                str_contains($l, 'Fingerprint') ||
                str_contains($l, 'sync-fingerspot') ||
                str_contains($l, 'reconcile') ||
                str_contains($l, 'auto-break-pause')
            );

            $relevant = array_slice(array_values($relevant), -$logLines);

            if (empty($relevant)) {
                $this->line('  <fg=yellow>⚠ Tidak ada aktivitas webhook/sync di log terbaru.</>');
                $this->line('  Kemungkinan: webhook belum dikonfigurasi di mesin, atau scheduler belum jalan.');
            } else {
                $this->line("  Menampilkan <comment>" . count($relevant) . "</comment> baris relevan terakhir:");
                $this->newLine();
                foreach (array_slice($relevant, -15) as $line) {
                    // Extract timestamp dan message
                    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?(\w+)\.(INFO|WARNING|ERROR|DEBUG): (.+)/', $line, $m)) {
                        $ts      = $m[1];
                        $level   = $m[3];
                        $message = substr($m[4], 0, 100);
                        $color   = match ($level) {
                            'ERROR'   => 'red',
                            'WARNING' => 'yellow',
                            default   => 'green',
                        };
                        $this->line("  <fg=gray>{$ts}</> [<fg={$color}>{$level}</>] {$message}");
                    } else {
                        $this->line('  ' . substr(trim($line), 0, 120));
                    }
                }
            }
        }

        // ── 8. Ringkasan ─────────────────────────────────────────────────────
        $this->newLine();
        $this->line('══════════════════════════════════════════════════════════');

        if ($allPassed && $rawCount > 0) {
            $this->line('<fg=green;options=bold>✓ SISTEM BERJALAN NORMAL — data fingerprint masuk otomatis</>');
        } elseif ($rawCount === 0) {
            $this->newLine();
            $this->line('<fg=yellow;options=bold>⚠ BELUM ADA DATA HARI INI. Kemungkinan penyebab:</>');
            $this->line('  1. Karyawan belum ada yang tap hari ini');
            $this->line('  2. Webhook belum dikonfigurasi di mesin fingerspot');
            $this->line('  3. Scheduler Laravel belum berjalan (cek crontab)');
            $this->line('  4. Mesin tidak terhubung ke jaringan server');
            $this->newLine();
            $this->line('  Tes manual:');
            $this->line('  <fg=yellow>  php artisan hr:sync-fingerspot --days=1</>');
        } else {
            $this->newLine();
            $this->line('<fg=red;options=bold>✗ ADA MASALAH — cek bagian yang merah di atas</>');
        }

        $this->line('══════════════════════════════════════════════════════════');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Baca N baris terakhir dari file tanpa load seluruh file ke memory.
     */
    private function tailFile(string $path, int $lines): array
    {
        $file   = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $start  = max(0, $totalLines - $lines);
        $result = [];

        $file->seek($start);
        while (!$file->eof()) {
            $result[] = $file->current();
            $file->next();
        }

        return $result;
    }
}
