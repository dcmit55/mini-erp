<?php

namespace App\Services;

use App\Models\FingerprintLog;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk merekonsiliasi raw fingerprint_logs menjadi attendance_logs
 * yang akurat berdasarkan jadwal kerja karyawan.
 *
 * Digunakan oleh:
 *  - FingerspotController::syncAttendance  (sync manual)
 *  - WebhookController::handle             (push otomatis dari mesin)
 */
class AttendanceReconcileService
{
    /** Toleransi ±menit saat mencocokkan scan ke shift_start / shift_end */
    const TOLERANCE_MINUTES = 15;

    /** Minimal selisih jam antara clock_in dan clock_out agar dianggap valid */
    const MIN_WORK_HOURS = 2;

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Normalisasi PIN dari employee_no.
     *   DCM-0528 → '528'
     *   DCM-0001 → '1'
     *   DCM-0000 → '0'  (edge case: PIN nol tidak hilang)
     */
    public function pinFromEmployeeNo(string $employeeNo): string
    {
        $numeric = preg_replace('/[^0-9]/', '', $employeeNo);
        return ltrim($numeric, '0') ?: '0';
    }

    /**
     * Normalisasi PIN raw dari mesin (buang leading zero, jaga '0').
     *   '0528' → '528',  '528' → '528',  '0' → '0'
     */
    public function normalizePin(string $rawPin): string
    {
        return ltrim($rawPin, '0') ?: '0';
    }

    /**
     * Cari Employee dari PIN mesin (angka saja, mis. '528').
     * Pencocokan hanya via format eksak DCM-XXXX, tidak pakai LIKE.
     */
    public function findEmployeeByPin(string $rawPin): ?Employee
    {
        $pin = $this->normalizePin($rawPin);

        // Cari DCM-0528 (4-digit padded)
        $employee = Employee::where(
            'employee_no', 'DCM-' . str_pad($pin, 4, '0', STR_PAD_LEFT)
        )->first();

        // Fallback: ada sistem yang simpan employee_no tanpa prefix (mis. '528')
        if (!$employee) {
            $employee = Employee::where('employee_no', $rawPin)->first();
        }

        return $employee;
    }

    /**
     * Simpan scan ke fingerprint_logs jika belum ada (idempoten).
     * Kembalikan true jika disimpan baru, false jika duplikat.
     */
    public function saveRawLog(string $rawPin, Carbon $scanCarbon, array $payload): bool
    {
        $pin = $this->normalizePin($rawPin);

        $alreadyExists = FingerprintLog::where('cloud_id', $pin)
            ->where('event_time', $scanCarbon->format('Y-m-d H:i:s'))
            ->exists();

        if ($alreadyExists) {
            return false;
        }

        FingerprintLog::create([
            'cloud_id'   => $pin,
            'event_time' => $scanCarbon,
            'payload'    => $payload,
        ]);

        return true;
    }

    /**
     * Rekonsiliasi attendance_logs dari raw fingerprint_logs untuk
     * satu karyawan pada satu tanggal.
     *
     * Alur:
     *  1. Ambil semua scan pada tanggal tersebut (+ hari berikutnya jika shift malam).
     *  2. Gunakan jadwal kerja (work policy) untuk menentukan target clock_in & clock_out.
     *  3. clock_in  = scan terdekat ke shift_start (dalam toleransi), fallback scan pertama.
     *  4. clock_out = scan yang ≥ MIN_WORK_HOURS setelah clock_in dan terdekat ke shift_end,
     *                 fallback scan terakhir yang valid.
     *  5. Jika tidak ada kandidat ≥ MIN_WORK_HOURS, clock_out = null.
     *
     * @param  int    $employeeId
     * @param  string $date        Format Y-m-d
     */
    public function reconcile(int $employeeId, string $date): void
    {
        $employee = Employee::with('workPolicy')->find($employeeId);
        if (!$employee) {
            Log::warning("AttendanceReconcileService: employee ID {$employeeId} tidak ditemukan");
            return;
        }

        $pin        = $this->pinFromEmployeeNo($employee->employee_no);
        $dateCarbon = Carbon::parse($date);
        $policy     = $employee->workPolicy;

        // ── Ambil jam shift ────────────────────────────────────────────────────
        [$shiftStart, $shiftEnd, $isOvernight] = $this->resolveShift($policy, $dateCarbon);

        // ── Kumpulkan scan ─────────────────────────────────────────────────────
        $scanTimes = $this->collectScans($pin, $date, $isOvernight, $shiftEnd);

        if ($scanTimes->isEmpty()) {
            Log::info("AttendanceReconcileService: tidak ada log PIN={$pin} tanggal={$date}");
            return;
        }

        // ── Tentukan clock_in ──────────────────────────────────────────────────
        $clockIn = $shiftStart
            ? ($this->findClosest($scanTimes, $shiftStart) ?? $scanTimes->first())
            : $scanTimes->first();

        // ── Kandidat clock_out: minimal MIN_WORK_HOURS setelah clock_in ────────
        $earliest   = $clockIn->copy()->addHours(self::MIN_WORK_HOURS);
        $candidates = $scanTimes->filter(fn($t) => $t->gte($earliest))->values();

        $clockOut = null;
        if ($candidates->isNotEmpty()) {
            $clockOut = $shiftEnd
                ? ($this->findClosest($candidates, $shiftEnd) ?? $candidates->last())
                : $candidates->last();
        }

        // ── Persist ─────────────────────────────────────────────────────────────
        $ciStr = $clockIn->format('H:i:s');
        $coStr = $clockOut?->format('H:i:s');

        AttendanceLog::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date],
            [
                'clock_in'      => $ciStr,
                'clock_out'     => $coStr,
                'import_source' => 'fingerprint_sync',
            ]
        );

        Log::info("AttendanceReconcileService: PIN={$pin} {$date} → clock_in={$ciStr}, clock_out=" . ($coStr ?? 'null'));
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Kembalikan [shiftStart|null, shiftEnd|null, isOvernight].
     * shiftStart & shiftEnd adalah Carbon dengan tanggal konkret dari $dateCarbon.
     */
    private function resolveShift($policy, Carbon $dateCarbon): array
    {
        if (!$policy) {
            return [null, null, false];
        }

        $dayOfWeek = strtolower($dateCarbon->format('l'));

        [$rawStart, $rawEnd] = match (true) {
            in_array($dayOfWeek, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                => [$policy->weekday_start, $policy->weekday_end],
            $dayOfWeek === 'saturday'
                => [$policy->saturday_start, $policy->saturday_end],
            $dayOfWeek === 'sunday'
                => [$policy->sunday_start, $policy->sunday_end],
            default => [null, null],
        };

        if (!$rawStart || !$rawEnd) {
            return [null, null, false];
        }

        $shiftStart  = Carbon::parse($rawStart)->setDateFrom($dateCarbon);
        $shiftEnd    = Carbon::parse($rawEnd)->setDateFrom($dateCarbon);
        $isOvernight = $shiftEnd->lt($shiftStart); // mis. 22:00–06:00

        if ($isOvernight) {
            $shiftEnd->addDay();
        }

        return [$shiftStart, $shiftEnd, $isOvernight];
    }

    /**
     * Ambil semua waktu scan untuk $pin pada $date.
     * Jika shift malam, sertakan scan dari hari berikutnya s/d batas shiftEnd + toleransi.
     *
     * @return \Illuminate\Support\Collection<int, Carbon>
     */
    private function collectScans(string $pin, string $date, bool $isOvernight, ?Carbon $shiftEnd)
    {
        $logs = FingerprintLog::where('cloud_id', $pin)
            ->whereDate('event_time', $date)
            ->orderBy('event_time')
            ->get();

        if ($isOvernight && $shiftEnd) {
            $cutoff   = $shiftEnd->copy()->addMinutes(self::TOLERANCE_MINUTES);
            $nextDate = Carbon::parse($date)->addDay()->format('Y-m-d');

            $nextLogs = FingerprintLog::where('cloud_id', $pin)
                ->whereDate('event_time', $nextDate)
                ->where('event_time', '<=', $cutoff)
                ->orderBy('event_time')
                ->get();

            $logs = $logs->merge($nextLogs)->sortBy('event_time')->values();
        }

        return $logs->map(fn($l) => Carbon::parse($l->event_time))->values();
    }

    /**
     * Cari Carbon dalam $scans yang paling dekat ke $target
     * dan masih dalam TOLERANCE_MINUTES. Null jika tidak ada.
     *
     * @param  \Illuminate\Support\Collection<int, Carbon>  $scans
     */
    private function findClosest($scans, Carbon $target): ?Carbon
    {
        $best     = null;
        $bestDiff = PHP_INT_MAX;

        foreach ($scans as $scan) {
            $diff = abs($scan->diffInMinutes($target));
            if ($diff <= self::TOLERANCE_MINUTES && $diff < $bestDiff) {
                $bestDiff = $diff;
                $best     = $scan;
            }
        }

        return $best;
    }
}
