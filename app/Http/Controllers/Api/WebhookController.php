<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FingerprintLog;
use App\Models\Hr\Employee;
use App\Models\Production\Timing;
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

        // ── Tap-type: 0=IN, 1=OUT, 2=Break-OUT, 3=Break-IN, 4=OT-IN, 5=OT-OUT ──
        $tapStatus = (int) ($request->input('status') ?? $request->input('tap_type') ?? -1);

        // Clock-IN → auto-stop any unfinished timings from previous days
        if ($tapStatus === 0) {
            $this->autoStopPreviousDayTimings($employee);
        }

        // Clock-OUT → auto-stop today's active timings
        if ($tapStatus === 1) {
            $this->autoStopTodayTimings($employee, $scanCarbon);
        }

        // Auto-set device_registered_at jika belum ada (pertama kali scan masuk webhook)
        $updates = [];

        if (is_null($employee->device_registered_at)) {
            $updates['device_registered_at'] = now();
        }

        // Set biometric_enrolled_at jika scan menggunakan biometric:
        // verify: 1=finger, 4=face, 6=vein
        $verify = (int) ($request->input('verify') ?? $request->input('verification') ?? -1);
        $isBiometricScan = in_array($verify, [1, 4, 6]);

        if ($isBiometricScan && is_null($employee->biometric_enrolled_at)) {
            $updates['biometric_enrolled_at'] = now();
            Log::info('Webhook: biometric_enrolled_at set', [
                'employee' => $employee->employee_no,
                'verify'   => $verify,
            ]);
        }

        if (!empty($updates)) {
            $employee->update($updates);
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

        // ── Regenerate daily_attendances (hanya untuk employee ini, bukan semua) ─
        try {
            app(DailyAttendanceService::class)->generateForEmployee($employee, Carbon::parse($date));
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
     * Clock-IN detected: auto-stop any timing from previous days that was never stopped.
     * Applies end_time = 23:59:59 on the timing's date as a graceful close.
     */
    private function autoStopPreviousDayTimings(Employee $employee): void
    {
        $timings = Timing::where('employee_id', $employee->id)
            ->whereDate('tanggal', '<', today())
            ->whereIn('status', ['on progress', 'frozen'])
            ->whereNull('end_time')
            ->get();

        foreach ($timings as $timing) {
            $endTime  = '23:59:59';
            $stoppedAt = Carbon::parse($timing->tanggal->format('Y-m-d') . ' ' . $endTime);
            $start    = Carbon::parse($timing->tanggal->format('Y-m-d') . ' ' . $timing->start_time);

            $deptData                    = $timing->department_specific_data ?? [];
            $deptData['auto_stopped']    = 'next_day_clock_in';
            $deptData['auto_stopped_at'] = now()->toDateTimeString();

            $timing->end_time                 = $endTime;
            $timing->stopped_at               = $stoppedAt;
            $timing->stop_reason              = 'Auto-stopped: employee clocked in next day without prior clock-out';
            $timing->status                   = 'complete';
            $timing->duration_minutes         = max(0, $start->diffInMinutes($stoppedAt));
            $timing->department_specific_data = $deptData;
            $timing->save();
        }

        if ($timings->count() > 0) {
            Log::info("Webhook: auto-stopped {$timings->count()} previous-day timing(s) for {$employee->name} (clock-in next day)");
        }
    }

    /**
     * Clock-OUT detected: auto-stop today's active timings using the clock-out time.
     */
    private function autoStopTodayTimings(Employee $employee, Carbon $clockOutTime): void
    {
        $endTime  = $clockOutTime->format('H:i:s');
        $endDate  = $clockOutTime->toDateString();
        $prevDate = $clockOutTime->copy()->subDay()->toDateString();

        // Include previous day to handle overnight / 24-hour shifts (Mascot can work past midnight)
        $timings = Timing::where('employee_id', $employee->id)
            ->whereIn('tanggal', [$endDate, $prevDate])
            ->whereIn('status', ['on progress', 'frozen'])
            ->whereNull('end_time')
            ->get();

        foreach ($timings as $timing) {
            // Use the session's own date for start time (not clock-out date) — overnight-safe
            $sessionDate = $timing->tanggal instanceof \Carbon\Carbon
                ? $timing->tanggal->format('Y-m-d')
                : $timing->tanggal;
            $start     = Carbon::parse($sessionDate . ' ' . $timing->start_time);
            $stoppedAt = Carbon::parse($endDate . ' ' . $endTime);
            // Guard: if clock-out somehow before start (bad data), skip
            if ($stoppedAt->lte($start)) {
                continue;
            }
            $gross = $start->diffInMinutes($stoppedAt);
            $net   = max(0, $gross - ($timing->total_paused_minutes ?? 0));

            $deptData                    = $timing->department_specific_data ?? [];
            $deptData['auto_stopped']    = 'clock_out';
            $deptData['auto_stopped_at'] = now()->toDateTimeString();

            $timing->end_time                 = $endTime;
            $timing->stopped_at               = $stoppedAt;
            $timing->stop_reason              = 'Auto-stopped: employee clocked out';
            $timing->status                   = 'complete';
            $timing->duration_minutes         = $net;
            $timing->department_specific_data = $deptData;
            $timing->save();
        }

        if ($timings->count() > 0) {
            Log::info("Webhook: auto-stopped {$timings->count()} timing(s) for {$employee->name} on clock-out at {$endTime}");
        }
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

        // Jika device mengirim data tapi kosong (mesin memang belum ada user),
        // tetap lanjut reconcile agar device_registered_at dibersihkan dari semua karyawan.
        // Jika field data tidak ada sama sekali di request, kemungkinan format webhook berbeda.
        $hasDataField = $request->has('data') || $request->has('pin_list') || $request->has('pins');

        if (!$hasDataField) {
            Log::info('Webhook get_all_pin: field data tidak ditemukan di payload', [
                'trans_id' => $transId,
                'fields'   => array_keys($request->all()),
            ]);
            Cache::forget($cacheKey);
            return response()->json(['success' => true, 'marked' => 0, 'cleared' => 0]);
        }

        // $rawData bisa kosong [] jika memang tidak ada user di mesin — tetap lanjut
        if (!is_array($rawData)) {
            $rawData = [];
        }

        // Bangun set employee_no yang benar-benar ada di mesin saat ini
        $deviceEmployeeNos = collect($rawData)
            ->map(function ($item) {
                $rawPin        = is_array($item) ? ($item['pin'] ?? '') : (string) $item;
                $normalizedPin = ltrim(preg_replace('/[^0-9]/', '', $rawPin), '0') ?: '0';
                $padded        = str_pad($normalizedPin, 4, '0', STR_PAD_LEFT);
                return 'DCM-' . $padded;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Tandai sebagai terdaftar di device (device → sistem)
        $marked = collect();
        if (!empty($deviceEmployeeNos)) {
            $marked = Employee::whereIn('employee_no', $deviceEmployeeNos)
                ->whereNull('device_registered_at')
                ->get();
            foreach ($marked as $employee) {
                $employee->update([
                    'device_registered_at'  => now(),
                    'biometric_enrolled_at' => now(),
                ]);
            }
        }

        // Hapus tanda registrasi untuk yang sudah tidak ada di mesin.
        // Jika $deviceEmployeeNos kosong (mesin kosong), bersihkan semua.
        $clearQuery = Employee::whereNotNull('device_registered_at');
        if (!empty($deviceEmployeeNos)) {
            $clearQuery->whereNotIn('employee_no', $deviceEmployeeNos);
        }
        $cleared = $clearQuery->get();
        foreach ($cleared as $employee) {
            $employee->update(['device_registered_at' => null]);
        }

        Cache::forget($cacheKey);

        Log::info('Webhook get_all_pin: sync selesai', [
            'trans_id'          => $transId,
            'device_pin_count'  => count($deviceEmployeeNos),
            'newly_marked'      => $marked->count(),
            'cleared_from_db'   => $cleared->count(),
            'cleared_employees' => $cleared->pluck('employee_no')->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'marked'  => $marked->count(),
            'cleared' => $cleared->count(),
        ]);
    }
}
