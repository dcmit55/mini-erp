<?php

namespace App\Console\Commands;

use App\Models\FingerprintLog;
use Illuminate\Console\Command;

class ProcessFingerprintLogs extends Command
{
    protected $signature = 'fingerprint:process
                            {--limit=100 : Jumlah record yang diproses per run}';

    protected $description = 'Proses fingerprint_logs ke attendance_logs berdasarkan pemetaan cloud_id → employee_id';

    public function handle(): int
    {
        // TODO: Implementasi pemetaan cloud_id → employee_id
        // Contoh alur yang akan diimplementasi:
        //
        // 1. Ambil FingerprintLog yang belum diproses (tambahkan kolom processed_at nanti)
        // 2. Untuk setiap log, cari employee berdasarkan cloud_id
        // 3. Tentukan apakah clock_in atau clock_out berdasarkan logika bisnis
        // 4. Upsert ke attendance_logs
        // 5. Tandai log sebagai processed

        $limit = (int) $this->option('limit');

        $logs = FingerprintLog::whereNull('processed_at')
            ->orderBy('event_time')
            ->limit($limit)
            ->get();

        if ($logs->isEmpty()) {
            $this->info('Tidak ada fingerprint log baru untuk diproses.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$logs->count()} log. Memulai proses...");

        $processed = 0;
        $skipped   = 0;

        foreach ($logs as $log) {
            // TODO: ganti dengan query ke tabel pemetaan cloud_id → employee_id
            // $employee = Employee::where('fingerprint_cloud_id', $log->cloud_id)->first();
            // if (!$employee) { $skipped++; continue; }

            // Placeholder — skip semua sampai pemetaan siap
            $this->warn("  [SKIP] Log #{$log->id} — cloud_id={$log->cloud_id} (belum ada pemetaan)");
            $skipped++;
        }

        $this->info("Selesai. Diproses: {$processed}, Dilewati: {$skipped}");

        return self::SUCCESS;
    }
}
