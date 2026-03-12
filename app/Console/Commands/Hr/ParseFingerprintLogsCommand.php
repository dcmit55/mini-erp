<?php

namespace App\Console\Commands\Hr;

use App\Models\FingerprintLog;
use App\Models\Hr\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * TAHAP 1: Parser Fingerprint
 *
 * Membaca fingerprint_logs yang belum diparsing (parsed_at IS NULL),
 * mengekstrak employee_id, direction, device_id dari payload JSON,
 * lalu mengisi kolom tersebut dan menandai parsed_at.
 *
 * Idempoten: hanya memproses baris yang parsed_at masih NULL.
 *
 * Jalankan: php artisan hr:parse-fingerprint [--date=2026-03-10] [--chunk=500]
 */
class ParseFingerprintLogsCommand extends Command
{
    protected $signature = 'hr:parse-fingerprint
                            {--date= : Proses hanya log pada tanggal tertentu (Y-m-d)}
                            {--chunk=500 : Jumlah record per batch}';

    protected $description = 'Parse raw fingerprint_logs payload → employee_id, direction, device_id';

    public function handle(): int
    {
        $date  = $this->option('date');
        $chunk = (int) $this->option('chunk');

        $query = FingerprintLog::whereNull('parsed_at');

        if ($date) {
            $query->whereDate('event_time', $date);
        }

        $total   = $query->count();
        $this->info("Memproses {$total} log yang belum diparsing...");
        $bar = $this->output->createProgressBar($total);

        $processed = 0;
        $errors    = 0;

        $query->chunkById($chunk, function ($logs) use (&$processed, &$errors, $bar) {
            foreach ($logs as $log) {
                try {
                    $parsed = $this->parsePayload($log->payload);

                    if (! $parsed) {
                        $errors++;
                        $bar->advance();
                        continue;
                    }

                    // Cari employee berdasarkan PIN (employee_no)
                    $employee = $this->resolveEmployee($parsed['pin'] ?? null, $parsed['employee_id'] ?? null);

                    $log->employee_id = $employee?->id;
                    $log->direction   = $this->resolveDirection($parsed['direction'] ?? null, $parsed['status'] ?? null);
                    $log->device_id   = $parsed['device_id'] ?? $parsed['sn'] ?? null;
                    $log->parsed_at   = now();
                    $log->saveQuietly(); // skip events/observers untuk performa

                    $processed++;
                } catch (\Throwable $e) {
                    $errors++;
                    \Log::warning("ParseFingerprint: log #{$log->id} gagal: " . $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Selesai. Berhasil: {$processed}, Error: {$errors}");

        return self::SUCCESS;
    }

    /**
     * Parse payload dari fingerprint_logs.
     *
     * Fingerspot API mengembalikan data dalam beberapa format.
     * Format attlog standar:
     *   payload JSON: {"pin": "0012", "time": "2026-03-10 08:05:00", "status": "0", "sn": "ABC123"}
     *
     * Status fingerspot:
     *   0 = Check-In, 1 = Check-Out, 2 = Break-Out, 3 = Break-In, 4 = OT-In, 5 = OT-Out
     */
    private function parsePayload(array|null $payload): ?array
    {
        if (empty($payload)) return null;

        // Format Fingerspot API standar (dari FingerspotService::getAttlog)
        if (isset($payload['pin'])) {
            return [
                'pin'       => ltrim($payload['pin'], '0') ?: '0',
                'direction' => $payload['status'] ?? null,
                'device_id' => $payload['sn'] ?? null,
            ];
        }

        // Format alternatif jika payload berbeda
        if (isset($payload['employee_id'])) {
            return [
                'employee_id' => $payload['employee_id'],
                'direction'   => $payload['direction'] ?? null,
                'device_id'   => $payload['device_id'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Map status fingerspot ke arah tap IN/OUT.
     * 0 = Check-In  → IN
     * 1 = Check-Out → OUT
     * 2 = Break-Out → OUT (keluar istirahat)
     * 3 = Break-In  → IN  (masuk dari istirahat)
     * 4 = OT-In     → IN
     * 5 = OT-Out    → OUT
     */
    private function resolveDirection(mixed $direction, mixed $status): ?string
    {
        // Jika sudah IN/OUT string
        if (in_array($direction, ['IN', 'OUT'])) {
            return $direction;
        }

        // Fingerspot numeric status
        $statusCode = $status ?? $direction;
        $inStatuses  = [0, 3, 4];
        $outStatuses = [1, 2, 5];

        if (in_array((int)$statusCode, $inStatuses))  return 'IN';
        if (in_array((int)$statusCode, $outStatuses)) return 'OUT';

        // Default: tidak dapat ditentukan
        return null;
    }

    private function resolveEmployee(?string $pin, ?int $employeeId): ?Employee
    {
        if ($employeeId) {
            return Employee::find($employeeId);
        }

        if ($pin !== null) {
            // Konversi PIN ke employee_no (DCM-XXXX format)
            // PIN "12" → employee_no "DCM-0012"
            $paddedPin = str_pad($pin, 4, '0', STR_PAD_LEFT);
            return Employee::where('employee_no', "DCM-{$paddedPin}")->first()
                ?? Employee::where('employee_no', $pin)->first();
        }

        return null;
    }
}
