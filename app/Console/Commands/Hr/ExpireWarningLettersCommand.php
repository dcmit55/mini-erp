<?php

namespace App\Console\Commands\Hr;

use App\Services\WarningLetterService;
use Illuminate\Console\Command;

class ExpireWarningLettersCommand extends Command
{
    protected $signature   = 'hr:expire-warning-letters';
    protected $description = 'Tandai warning letter yang sudah melewati valid_until sebagai expired. Cek recovery karyawan.';

    public function handle(WarningLetterService $service): int
    {
        $this->info('[hr:expire-warning-letters] Mulai proses...');

        try {
            $result = $service->expireOverdue();

            $this->info("  → {$result['expired_count']} letter di-expire.");

            if (!empty($result['recovered_employees'])) {
                $this->info('  → Karyawan yang pulih (semua SP expired):');
                foreach ($result['recovered_employees'] as $empId) {
                    $this->line("     - Employee #{$empId}");
                }
            } else {
                $this->line('  → Tidak ada karyawan yang baru pulih.');
            }

            \Log::info('[hr:expire-warning-letters] Selesai.', [
                'expired_count'       => $result['expired_count'],
                'recovered_employees' => $result['recovered_employees'],
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            \Log::error('[hr:expire-warning-letters] Gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
