<?php

namespace App\Console\Commands\Hr;

use App\Services\FingerspotService;
use App\Services\AttendanceReconcileService;
use App\Services\DailyAttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pull data absensi dari Fingerspot API → simpan ke fingerprint_logs → rekonsiliasi.
 *
 * Dijalankan otomatis setiap 5 menit via scheduler (Kernel.php).
 * Idempoten: duplikat scan dilewati tanpa error.
 *
 * Jalankan manual: php artisan hr:sync-fingerspot [--date=2026-03-12] [--days=2]
 */
class SyncFingerspotCommand extends Command
{
    protected $signature = 'hr:sync-fingerspot
                            {--date= : Sync tanggal tertentu (Y-m-d)}
                            {--days=2 : Jumlah hari ke belakang yang di-sync (default: 2)}';

    protected $description = 'Pull data absensi dari Fingerspot API dan simpan ke fingerprint_logs (auto setiap 5 menit)';

    public function __construct(
        protected FingerspotService          $fingerspot,
        protected AttendanceReconcileService $reconciler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $deviceId = config('fingerspot.device_id');

        if (!$deviceId) {
            $this->error('FINGERSPOT_DEVICE_ID tidak dikonfigurasi di .env');
            return self::FAILURE;
        }

        if ($this->option('date')) {
            $startDate = Carbon::parse($this->option('date'));
            $endDate   = $startDate->copy();
        } else {
            $days      = max(1, (int) $this->option('days'));
            $endDate   = Carbon::today();
            $startDate = Carbon::today()->subDays($days - 1);
        }

        $this->line("Sync fingerspot [{$deviceId}]: {$startDate->toDateString()} s/d {$endDate->toDateString()}");

        $saved         = 0;
        $duplicates    = 0;
        $notMatched    = 0;
        $affectedPairs = [];

        try {
            $current = $startDate->copy();

            while ($current->lte($endDate)) {
                // Fingerspot API max 2 hari per request
                $chunkEnd = $current->copy()->addDay();
                if ($chunkEnd->gt($endDate)) {
                    $chunkEnd = $endDate->copy();
                }

                $response = $this->fingerspot->getAttlog(
                    $deviceId,
                    $current->format('Y-m-d'),
                    $chunkEnd->format('Y-m-d')
                );

                $records = $response['data'] ?? [];

                if (empty($records)) {
                    $current->addDays(2);
                    continue;
                }

                foreach ($records as $record) {
                    $pinRaw  = (string) ($record['pin'] ?? $record['cloud_id'] ?? '');
                    $timeRaw = $record['scan_date'] ?? $record['time'] ?? $record['scan_time'] ?? null;

                    if (!$pinRaw || !$timeRaw) {
                        continue;
                    }

                    $employee = $this->reconciler->findEmployeeByPin($pinRaw);

                    if (!$employee) {
                        $notMatched++;
                        continue;
                    }

                    // Auto-set device_registered_at jika belum ada
                    if (is_null($employee->device_registered_at)) {
                        $employee->update(['device_registered_at' => now()]);
                    }

                    $scanCarbon = Carbon::parse($timeRaw);
                    $isSaved    = $this->reconciler->saveRawLog($pinRaw, $scanCarbon, $record);

                    if (!$isSaved) {
                        $duplicates++;
                        continue;
                    }

                    $date = $scanCarbon->format('Y-m-d');
                    $affectedPairs[$employee->id][$date] = true;
                    $saved++;
                }

                $current->addDays(2);
            }
        } catch (\Exception $e) {
            Log::error('hr:sync-fingerspot error: ' . $e->getMessage());
            $this->error('Sync gagal: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Rekonsiliasi & regenerate DailyAttendance hanya untuk data baru
        $allAffectedDates = [];
        foreach ($affectedPairs as $employeeId => $dates) {
            foreach (array_keys($dates) as $date) {
                $this->reconciler->reconcile($employeeId, $date);
                $allAffectedDates[$date] = true;
            }
        }

        foreach (array_keys($allAffectedDates) as $date) {
            app(DailyAttendanceService::class)->generateForDate(Carbon::parse($date));
        }

        $this->info("Selesai. Baru: {$saved}, Duplikat: {$duplicates}, PIN tidak cocok: {$notMatched}");

        Log::info('hr:sync-fingerspot selesai', [
            'saved'      => $saved,
            'duplicates' => $duplicates,
            'notMatched' => $notMatched,
            'range'      => $startDate->toDateString() . ' s/d ' . $endDate->toDateString(),
        ]);

        return self::SUCCESS;
    }
}
