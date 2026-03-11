<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FingerprintLog;
use App\Services\AttendanceReconcileService;
use App\Services\DailyAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
}
