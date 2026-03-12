<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FingerprintLog;
use App\Models\Hr\Employee;
use App\Services\AttendanceReconcileService;
use App\Services\DailyAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Menerima push data scan dari mesin fingerprint (otomatis, tanpa aksi HR).
 *
 * Endpoint:
 *   POST /api/webhook/fingerprint           (test, tanpa auth)
 *   POST /api/webhook/fingerprint/{uuid}    (produksi, dengan token + throttle)
 *
 * Alur:
 *  1. Validasi field wajib (PIN karyawan + waktu scan).
 *  2. Simpan raw payload ke fingerprint_logs — idempoten (skip duplikat).
 *  3. Cari karyawan via AttendanceReconcileService::findEmployeeByPin.
 *  4. Jika ketemu, rekonsiliasi attendance_logs untuk tanggal scan.
 *  5. Regenerate daily_attendances.
 *  6. Selalu kembalikan HTTP 200 agar mesin tidak retry berulang.
 */
class WebhookController extends Controller
{
    protected AttendanceReconcileService $reconciler;

    public function __construct(AttendanceReconcileService $reconciler)
    {
        $this->reconciler = $reconciler;
    }

    public function handle(Request $request)
    {
        Log::info('Fingerprint webhook received', [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        // ── Deteksi apakah ini respons get_all_pin (async sync) ───────────────
        // Mesin mengirim semua event ke satu webhook URL yang sama.
        // Respons get_all_pin dikenali dari trans_id yang ada di cache.
        $transId = $request->input('trans_id', '');
        if ($transId && Cache::has('sync_' . $transId)) {
            return $this->handleGetAllPinWebhook($request, $transId);
        }

        // ── Ekstrak PIN dan waktu scan dari payload ────────────────────────────
        // Fingerspot mengirim data dalam berbagai format tergantung firmware:
        //   cloud_id / pin / user_id  → ID karyawan di mesin
        //   scan_time / time / timestamp → waktu scan
        $pinRaw   = (string) ($request->input('id')
            ?? $request->input('employee_id')
            ?? $request->input('user_id')
            ?? '');

        $timeRaw  = $request->input('scan_time')
            ?? $request->input('scan_date')
            ?? $request->input('time')
            ?? $request->input('timestamp');

        if (!$pinRaw || !$timeRaw) {
            Log::warning('Webhook: field wajib tidak lengkap', [
                'fields_received' => array_keys($request->all()),
                'pin'             => $pinRaw  ?: null,
                'time'            => $timeRaw ?: null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Missing required fields: employee PIN (cloud_id/pin) and scan time (scan_time/time/timestamp).',
                'fields'  => array_keys($request->all()),
            ], 422);
        }

        $scanCarbon = Carbon::parse($timeRaw);
        $date       = $scanCarbon->format('Y-m-d');

        // ── Simpan raw log (idempoten) ─────────────────────────────────────────
        $isNew = $this->reconciler->saveRawLog($pinRaw, $scanCarbon, $request->all());

        Log::info('FingerprintLog ' . ($isNew ? 'saved' : 'duplicate skipped'), [
            'pin'  => $pinRaw,
            'time' => $scanCarbon->toDateTimeString(),
        ]);

        // ── Cari karyawan ──────────────────────────────────────────────────────
        $employee = $this->reconciler->findEmployeeByPin($pinRaw);

        if (!$employee) {
            Log::warning('Webhook: karyawan tidak ditemukan', [
                'pin'       => $pinRaw,
                'normalized'=> $this->reconciler->normalizePin($pinRaw),
            ]);

            // Tetap 200 agar mesin tidak retry terus — raw log sudah tersimpan
            return response()->json([
                'success'    => true,
                'message'    => 'Scan recorded (employee not matched in system)',
                'pin'        => $pinRaw,
                'is_new_log' => $isNew,
            ]);
        }

        // Auto-set device_registered_at jika belum ada (pertama kali scan masuk webhook)
        if (is_null($employee->device_registered_at)) {
            $employee->update(['device_registered_at' => now()]);
        }

        // ── Rekonsiliasi attendance_logs ───────────────────────────────────────
        // Jalankan meski $isNew = false, karena bisa saja rekonsiliasi sebelumnya gagal
        try {
            $this->reconciler->reconcile($employee->id, $date);
        } catch (\Exception $e) {
            Log::error('Webhook: reconcile gagal', [
                'employee_id' => $employee->id,
                'date'        => $date,
                'error'       => $e->getMessage(),
            ]);
        }

        // ── Regenerate daily_attendances ───────────────────────────────────────
        try {
            app(DailyAttendanceService::class)->generateForDate(Carbon::parse($date));
        } catch (\Exception $e) {
            Log::error('Webhook: regenerate daily_attendances gagal: ' . $e->getMessage());
        }

        Log::info('Webhook processed', [
            'employee_id' => $employee->id,
            'employee'    => $employee->name,
            'date'        => $date,
            'is_new_log'  => $isNew,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Scan recorded and processed',
            'employee'    => $employee->name,
            'employee_no' => $employee->employee_no,
            'date'        => $date,
            'time'        => $scanCarbon->format('H:i:s'),
            'is_new_log'  => $isNew,
            'received_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Proses respons get_all_pin dari mesin.
     * Dipanggil dari handle() ketika trans_id cocok dengan cache sync.
     */
    private function handleGetAllPinWebhook(Request $request, string $transId)
    {
        $cacheKey = 'sync_' . $transId;

        // Coba berbagai kemungkinan field nama data PIN
        $rawData = $request->input('data')
            ?? $request->input('pin_list')
            ?? $request->input('pins')
            ?? [];

        if (empty($rawData) || !is_array($rawData)) {
            Log::info('Webhook get_all_pin: no PIN data', [
                'trans_id' => $transId,
                'fields'   => array_keys($request->all()),
            ]);
            Cache::forget($cacheKey);
            return response()->json(['success' => true, 'updated' => 0]);
        }

        $updated = 0;
        foreach ($rawData as $item) {
            $rawPin        = is_array($item) ? ($item['pin'] ?? '') : (string) $item;
            $normalizedPin = ltrim(preg_replace('/[^0-9]/', '', $rawPin), '0') ?: '0';
            $padded        = str_pad($normalizedPin, 4, '0', STR_PAD_LEFT);

            $employee = Employee::where('employee_no', 'DCM-' . $padded)
                ->whereNull('device_registered_at')
                ->first();

            if ($employee) {
                $employee->update(['device_registered_at' => now()]);
                $updated++;
            }
        }

        Cache::forget($cacheKey);
        Log::info("Webhook get_all_pin: {$updated} employees marked as on device", ['trans_id' => $transId]);

        return response()->json(['success' => true, 'updated' => $updated]);
    }
}
