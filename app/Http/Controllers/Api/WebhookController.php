<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FingerprintLog;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\Employee;
use App\Services\DailyAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming scan dari mesin fingerprint.
     *
     * Payload dari Fingerspot:
     *   cloud_id  : ID karyawan di mesin (misal "528") — BUKAN device ID
     *   pin       : fallback jika cloud_id tidak ada
     *   scan_time | time | timestamp : waktu scan
     *
     * Catatan: device ID mesin (misal C2656C741B331925) ada di .env sebagai
     *          FINGERSPOT_DEVICE_ID dan tidak dikirim dalam webhook scan.
     */
    public function handle(Request $request)
    {
        Log::info('Fingerprint webhook received', [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        // ── Ekstrak ID karyawan di mesin ─────────────────────────────────────
        // cloud_id di webhook = ID karyawan di mesin (mis. "528")
        // pin = fallback untuk format firmware tertentu
        $employeePin = $request->input('cloud_id')
            ?? $request->input('pin')
            ?? $request->input('user_id');

        // ── Ekstrak waktu scan ───────────────────────────────────────────────
        // Fingerspot firmware berbeda-beda: scan_time, time, atau timestamp
        $scanTime = $request->input('scan_time')
            ?? $request->input('time')
            ?? $request->input('timestamp');

        // ── Validasi field wajib ─────────────────────────────────────────────
        if (!$employeePin || !$scanTime) {
            Log::warning('Webhook: missing required fields', [
                'received_fields' => array_keys($request->all()),
                'employee_pin'    => $employeePin,
                'scan_time'       => $scanTime,
            ]);

            return response()->json([
                'success'  => false,
                'message'  => 'Missing required fields. Need employee ID (cloud_id/pin) and scan time (scan_time/time/timestamp).',
                'received' => array_keys($request->all()),
            ], 422);
        }

        // ── Simpan raw payload ke fingerprint_logs ───────────────────────────
        $fingerprintLog = FingerprintLog::create([
            'cloud_id'   => (string) $employeePin,
            'event_time' => Carbon::parse($scanTime),
            'payload'    => $request->all(),
        ]);

        Log::info('FingerprintLog saved', [
            'id'          => $fingerprintLog->id,
            'employee_pin'=> $employeePin,
        ]);

        // ── Cari employee berdasarkan ID karyawan di mesin ───────────────────
        // Contoh: cloud_id = "528" → cocokkan ke DCM-0528
        $employee = Employee::where('employee_no', 'DCM-' . str_pad($employeePin, 4, '0', STR_PAD_LEFT))
            ->orWhere('employee_no', 'DCM-' . ltrim((string) $employeePin, '0'))
            ->orWhere('employee_no', (string) $employeePin)
            ->orWhere('employee_no', 'like', '%-' . ltrim((string) $employeePin, '0'))
            ->first();

        if (!$employee) {
            Log::warning('Webhook: employee not found', [
                'employee_pin'       => $employeePin,
                'fingerprint_log_id' => $fingerprintLog->id,
                'tried_formats'      => [
                    'DCM-' . str_pad($employeePin, 4, '0', STR_PAD_LEFT),
                    'DCM-' . ltrim((string) $employeePin, '0'),
                    (string) $employeePin,
                ],
            ]);

            // Tetap return 200 agar mesin tidak retry terus-menerus
            // Data sudah tersimpan di fingerprint_logs untuk audit
            return response()->json([
                'success' => true,
                'message' => 'Scan recorded (employee not matched in system)',
                'pin'     => $employeePin,
            ]);
        }

        // ── Parse waktu scan ─────────────────────────────────────────────────
        $scanCarbon = Carbon::parse($scanTime);
        $date       = $scanCarbon->format('Y-m-d');
        $timeOnly   = $scanCarbon->format('H:i:s');

        // ── Update atau buat attendance_log ──────────────────────────────────
        $attendanceLog = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->first();

        $action = '';

        if (!$attendanceLog) {
            AttendanceLog::create([
                'employee_id'   => $employee->id,
                'date'          => $date,
                'clock_in'      => $timeOnly,
                'clock_out'     => null,
                'import_source' => 'fingerprint',
            ]);
            $action = 'clock_in';

        } elseif (is_null($attendanceLog->clock_in)) {
            $attendanceLog->clock_in = $timeOnly;
            $attendanceLog->save();
            $action = 'clock_in';

        } elseif (is_null($attendanceLog->clock_out)) {
            $attendanceLog->clock_out = $timeOnly;
            $attendanceLog->save();
            $action = 'clock_out';

        } else {
            Log::info('Webhook: extra scan recorded in logs but attendance already complete', [
                'employee_id' => $employee->id,
                'date'        => $date,
                'extra_time'  => $timeOnly,
            ]);
            $action = 'extra_scan';
        }

        // ── Regenerate daily_attendances ─────────────────────────────────────
        try {
            app(DailyAttendanceService::class)->generateForDate(Carbon::parse($date));
        } catch (\Exception $e) {
            Log::error('Failed to regenerate daily_attendances: ' . $e->getMessage());
        }

        Log::info('Webhook processed', [
            'employee_id' => $employee->id,
            'employee'    => $employee->name,
            'date'        => $date,
            'time'        => $timeOnly,
            'action'      => $action,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Scan recorded',
            'action'      => $action,
            'employee'    => $employee->name,
            'employee_no' => $employee->employee_no,
            'date'        => $date,
            'time'        => $timeOnly,
            'received_at' => now()->toIso8601String(),
        ]);
    }
}
