<?php

namespace App\Console\Commands\Hr;

use App\Models\Hr\Employee;
use App\Services\FingerspotService;
use App\Services\AttendanceReconcileService;
use App\Services\DailyAttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Full sync mesin ↔ sistem:
 *   1. Pull data absensi (getAttlog) → fingerprint_logs → rekonsiliasi attendance
 *   2. Pull daftar PIN (getAllPin) → reconcile device_registered_at (tambah + hapus)
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

    protected $description = 'Full sync: pull absensi + sync registrasi karyawan dari mesin Fingerspot';

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

        // Terapkan batas minimum tanggal (FINGERSPOT_SYNC_VALID_FROM di .env).
        // Berguna ketika data lama di cloud Fingerspot tidak bisa dihapus via API.
        $syncValidFrom = config('fingerspot.sync_valid_from');
        if ($syncValidFrom) {
            $validFrom = Carbon::parse($syncValidFrom);
            if ($startDate->lt($validFrom)) {
                $startDate = $validFrom;
            }
            // Jika end date pun sebelum valid_from, tidak ada yang perlu di-sync
            if ($endDate->lt($validFrom)) {
                $this->line('Semua tanggal sebelum FINGERSPOT_SYNC_VALID_FROM, sync dilewati.');
                return self::SUCCESS;
            }
        }

        $this->line("Sync fingerspot [{$deviceId}]: {$startDate->toDateString()} s/d {$endDate->toDateString()}");

        // ── 1) Pull attendance data ──────────────────────────────────────────
        $saved         = 0;
        $duplicates    = 0;
        $notMatched    = 0;
        $affectedPairs = [];

        try {
            $current = $startDate->copy();

            while ($current->lte($endDate)) {
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

                    $scanCarbon = Carbon::parse($timeRaw);
                    $isSaved    = $this->reconciler->saveRawLog($pinRaw, $scanCarbon, $record);

                    if (!$isSaved) {
                        $duplicates++;
                        continue;
                    }

                    // Hanya set field registrasi untuk scan BARU (bukan historis/duplikat)
                    $updates = [];
                    if (is_null($employee->device_registered_at)) {
                        $updates['device_registered_at'] = now();
                    }
                    if (is_null($employee->biometric_enrolled_at)) {
                        $updates['biometric_enrolled_at'] = now();
                    }
                    if (!empty($updates)) {
                        $employee->update($updates);
                    }

                    $date = $scanCarbon->format('Y-m-d');
                    $affectedPairs[$employee->id][$date] = true;
                    $saved++;
                }

                $current->addDays(2);
            }
        } catch (\Exception $e) {
            Log::error('hr:sync-fingerspot attendance error: ' . $e->getMessage());
            $this->error('Sync absensi gagal: ' . $e->getMessage());
            return self::FAILURE;
        }

        // ── 2) Rekonsiliasi & regenerate DailyAttendance ─────────────────────
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

        // ── 3) Sync registrasi via getAllPin ─────────────────────────────────
        $regMarked  = 0;
        $regCleared = 0;

        try {
            $pinTransId = uniqid('sync_', true);
            Cache::put('sync_' . $pinTransId, ['status' => 'pending', 'started_at' => now()->toIso8601String()], now()->addMinutes(10));

            $allPinResponse = $this->fingerspot->getAllPin($deviceId, $pinTransId);
            $devicePins     = $this->parseAllPinResponse($allPinResponse);

            // Cek apakah device merespons secara sinkron (ada key data/pin/pins di response),
            // vs async (hanya success:true tanpa data — device akan callback via webhook).
            $deviceRespondedSync = array_key_exists('data', $allPinResponse)
                || array_key_exists('pin',  $allPinResponse)
                || array_key_exists('pins', $allPinResponse);

            if ($deviceRespondedSync) {
                // Device langsung kirim list PIN (bisa kosong jika memang belum ada user)
                Cache::forget('sync_' . $pinTransId);
                [$regMarked, $regCleared] = $this->reconcileDevicePins($devicePins);
            } elseif ($allPinResponse['success'] ?? false) {
                // Async — webhook callback akan handle dengan trans_id ini
                Log::info('hr:sync-fingerspot getAllPin: menunggu webhook callback', ['trans_id' => $pinTransId]);
            }
        } catch (\Exception $e) {
            Log::warning('hr:sync-fingerspot getAllPin gagal: ' . $e->getMessage());
        }

        Cache::forget('fingerspot_stats');

        // ── 4) Summary ──────────────────────────────────────────────────────
        $this->info("Selesai. Baru: {$saved}, Duplikat: {$duplicates}, PIN tak cocok: {$notMatched}, Reg baru: {$regMarked}, Dihapus: {$regCleared}");

        Log::info('hr:sync-fingerspot selesai', [
            'saved'       => $saved,
            'duplicates'  => $duplicates,
            'notMatched'  => $notMatched,
            'reg_marked'  => $regMarked,
            'reg_cleared' => $regCleared,
            'range'       => $startDate->toDateString() . ' s/d ' . $endDate->toDateString(),
        ]);

        return self::SUCCESS;
    }

    // ─── Helper: parse response getAllPin ─────────────────────────────────────

    private function parseAllPinResponse(array $apiResult): array
    {
        $raw = $apiResult['data'] ?? $apiResult['pin'] ?? $apiResult['pins'] ?? null;

        if (is_null($raw)) {
            return [];
        }

        if (is_string($raw)) {
            $pins = preg_split('/[\s,;|]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
            return collect($pins)
                ->map(fn($p) => $this->reconciler->normalizePin(trim($p)))
                ->filter()
                ->values()
                ->toArray();
        }

        if (is_array($raw)) {
            return collect($raw)
                ->map(function ($item) {
                    if (is_array($item)) {
                        $pin = $item['pin'] ?? $item['no'] ?? $item['id'] ?? null;
                    } elseif (is_string($item) || is_numeric($item)) {
                        $pin = (string) $item;
                    } else {
                        $pin = null;
                    }
                    return $pin ? $this->reconciler->normalizePin(trim($pin)) : null;
                })
                ->filter()
                ->values()
                ->toArray();
        }

        return [];
    }

    // ─── Helper: reconcile device PIN list ↔ database ────────────────────────

    private function reconcileDevicePins(array $normalizedPins): array
    {
        $deviceEmployeeNos = collect($normalizedPins)
            ->map(fn($pin) => 'DCM-' . str_pad($pin, 4, '0', STR_PAD_LEFT))
            ->unique()
            ->values()
            ->toArray();

        // Tandai yang ada di mesin tapi belum tercatat di sistem
        $marked = collect();
        if (!empty($deviceEmployeeNos)) {
            $marked = Employee::whereIn('employee_no', $deviceEmployeeNos)
                ->whereNull('device_registered_at')
                ->get();
            foreach ($marked as $emp) {
                $emp->update([
                    'device_registered_at'  => now(),
                    'biometric_enrolled_at' => now(),
                ]);
            }
        }

        // Hapus tanda untuk yang sudah tidak ada di mesin.
        // Jika $deviceEmployeeNos kosong (mesin kosong), bersihkan semua.
        $clearQuery = Employee::whereNotNull('device_registered_at');
        if (!empty($deviceEmployeeNos)) {
            $clearQuery->whereNotIn('employee_no', $deviceEmployeeNos);
        }
        $cleared = $clearQuery->get();
        foreach ($cleared as $emp) {
            $emp->update(['device_registered_at' => null, 'biometric_enrolled_at' => null]);
        }

        Log::info('reconcileDevicePins (auto-sync)', [
            'device_pins'  => count($deviceEmployeeNos),
            'marked'       => $marked->count(),
            'cleared'      => $cleared->count(),
            'cleared_nos'  => $cleared->pluck('employee_no')->toArray(),
        ]);

        return [$marked->count(), $cleared->count()];
    }
}
