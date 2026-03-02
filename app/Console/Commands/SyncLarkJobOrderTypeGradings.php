<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Lark\LarkJobOrderTypeGradingSyncService;

class SyncLarkJobOrderTypeGradings extends Command
{
    protected $signature = 'lark:sync-job-order-type-gradings
                            {--dry-run : Tampilkan data dari Lark tanpa menyimpan ke database}
                            {--debug : Tampilkan detail raw response dari Lark}';

    protected $description = 'Sync Job Order Type Gradings dari Lark ke database';

    public function handle(LarkJobOrderTypeGradingSyncService $service): int
    {
        $this->info('Starting Lark Job Order Type Gradings sync...');

        // Debug mode: tampilkan raw response saja
        if ($this->option('debug')) {
            $this->info('=== DEBUG: Raw Response dari Lark ===');
            try {
                $raw = $service->getRawResponse();
                $this->line(json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                $this->error('Gagal mengambil raw response: ' . $e->getMessage());
                return self::FAILURE;
            }
            return self::SUCCESS;
        }

        // Dry-run mode: fetch dan tampilkan tanpa simpan
        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN mode aktif - data tidak akan disimpan.');
            try {
                $raw = $service->getRawResponse();
                $records = $raw['data']['items'] ?? [];
                $this->info('Total records di Lark: ' . count($records));
                foreach ($records as $index => $record) {
                    $fields = $record['fields'] ?? [];
                    $this->line(sprintf(
                        '[%d] record_id=%s | Job Type Grade=%s | Score=%s | Grading=%s | Dept=%s',
                        $index + 1,
                        $record['record_id'] ?? '-',
                        $fields['Job Type Grade'] ?? '-',
                        $fields['Score'] ?? '-',
                        $fields['Grading'] ?? '-',
                        is_array($fields['Department'] ?? null)
                            ? implode(', ', array_column($fields['Department'], 'text'))
                            : ($fields['Department'] ?? '-'),
                    ));
                }
            } catch (\Exception $e) {
                $this->error('Gagal mengambil data: ' . $e->getMessage());
                return self::FAILURE;
            }
            return self::SUCCESS;
        }

        // Sync normal
        try {
            $stats = $service->sync();

            $this->info('');
            $this->info('=== SYNC SUMMARY ===');
            $this->info("  Fetched from Lark : {$stats['fetched']}");
            $this->info("  Created           : {$stats['created']}");
            $this->info("  Updated           : {$stats['updated']}");
            $this->info("  Deactivated       : {$stats['deactivated']}");

            if ($stats['errors'] > 0) {
                $this->warn("  Errors            : {$stats['errors']}");
                foreach ($stats['error_details'] as $err) {
                    $this->warn("    - record_id={$err['record_id']}: {$err['error']}");
                }
            } else {
                $this->info("  Errors            : 0");
            }

            $this->info('Sync completed successfully.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
