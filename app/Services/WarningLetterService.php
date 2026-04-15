<?php

namespace App\Services;

use App\Models\Hr\Employee;
use App\Models\Hr\WarningBatch;
use App\Models\Hr\WarningLetter;
use App\Models\Hr\WarningTemplate;
use App\Models\Hr\ViolationCategory;
use App\Models\Hr\ViolationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use RuntimeException;

class WarningLetterService
{
    // ─── SP Level Determination ───────────────────────────────────────────────

    /**
     * Tentukan SP level berikutnya untuk seorang karyawan.
     *
     * Logic:
     *   - Cek SP aktif (status bukan expired/rejected, valid_until >= hari ini)
     *   - NULL  → SP1
     *   - SP1   → SP2
     *   - SP2   → SP3
     *   - SP3   → SP4
     *   - SP4   → throw RuntimeException (flag untuk Termination)
     *
     * @throws RuntimeException jika SP4 masih aktif (wajib proses PHK)
     */
    public function determineSpLevel(int $employeeId): int
    {
        $currentMax = WarningLetter::where('employee_id', $employeeId)
            ->whereNotIn('status', ['expired', 'rejected'])
            ->where('valid_until', '>=', now()->toDateString())
            ->max('sp_level');

        if ($currentMax === null) {
            return 1;
        }

        if ($currentMax >= 4) {
            throw new RuntimeException(
                "Karyawan memiliki SP4 yang masih aktif. Proses ini harus dialihkan ke Pemutusan Hubungan Kerja (PHK)."
            );
        }

        return $currentMax + 1;
    }

    /**
     * Ambil SP aktif yang sedang berjalan untuk seorang karyawan (untuk ditampilkan di form).
     */
    public function getActiveSpLevel(int $employeeId): ?int
    {
        return WarningLetter::where('employee_id', $employeeId)
            ->whereNotIn('status', ['expired', 'rejected'])
            ->where('valid_until', '>=', now()->toDateString())
            ->max('sp_level');
    }

    // ─── Letter Number Generator ──────────────────────────────────────────────

    /**
     * Generate nomor surat yang unik dengan format:
     *   SP/{spLevel}/{DEPT}/{YYYY}/{NNN}
     *   Contoh: SP/2/PROD/2026/003
     *
     * Sequence dihitung per kombinasi spLevel + dept + tahun.
     */
    public function generateLetterNumber(int $spLevel, string $deptName, Carbon $issuedDate): string
    {
        $deptCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $deptName), 0, 4));
        $year     = $issuedDate->format('Y');
        $prefix   = "SP/{$spLevel}/{$deptCode}/{$year}/";

        // Hitung sequence terakhir untuk kombinasi ini (termasuk soft-deleted)
        $last = WarningLetter::withTrashed()
            ->where('letter_number', 'like', $prefix . '%')
            ->orderByDesc('letter_number')
            ->value('letter_number');

        $sequence = 1;
        if ($last) {
            $parts    = explode('/', $last);
            $sequence = (int) end($parts) + 1;
        }

        return $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    // ─── Single Warning Letter ────────────────────────────────────────────────

    /**
     * Buat satu Warning Letter baru (status: draft).
     *
     * Data yang dibutuhkan:
     *   - employee_id, violation_cat_id, violation_date, reason
     *   - created_by
     *   - sp_level (optional, kalau tidak diisi akan di-determine otomatis)
     *   - template_id (optional)
     *
     * @throws RuntimeException jika SP4 masih aktif
     */
    public function createSingle(array $data): WarningLetter
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::with('department')->findOrFail($data['employee_id']);
            $deptName = $employee->department?->name ?? 'GEN';

            $spLevel   = $data['sp_level'] ?? $this->determineSpLevel($employee->id);
            $issuedDate = Carbon::today();
            $validUntil = $issuedDate->copy()->addDays(180);

            $letterNumber = $this->generateLetterNumber($spLevel, $deptName, $issuedDate);

            $templateId = $data['template_id']
                ?? WarningTemplate::forLevel($spLevel)->value('id');

            $letter = WarningLetter::create([
                'uid'              => (string) Str::uuid(),
                'letter_number'    => $letterNumber,
                'employee_id'      => $employee->id,
                'sp_level'         => $spLevel,
                'violation_cat_id' => $data['violation_cat_id'],
                'violation_date'   => $data['violation_date'],
                'reason'           => $data['reason'],
                'status'           => 'draft',
                'template_id'      => $templateId,
                'issued_date'      => $issuedDate,
                'valid_until'      => $validUntil,
                'batch_id'         => $data['batch_id'] ?? null,
                'created_by'       => $data['created_by'],
                'trigger_source'   => $data['trigger_source'] ?? 'manual',
            ]);

            // Catat di violation_log
            ViolationLog::create([
                'employee_id'      => $employee->id,
                'violation_cat_id' => $data['violation_cat_id'],
                'violation_date'   => $data['violation_date'],
                'source'           => $data['trigger_source'] ?? 'manual',
                'warning_letter_id'=> $letter->id,
                'batch_id'         => $data['batch_id'] ?? null,
                'notes'            => $data['reason'],
            ]);

            return $letter;
        });
    }

    // ─── Bulk Warning Letter ──────────────────────────────────────────────────

    /**
     * Buat Bulk Warning Letter untuk banyak karyawan sekaligus.
     *
     * Flow:
     * 1. Buat satu WarningBatch sebagai container
     * 2. Loop setiap employee_id → determineSpLevel individual
     * 3. createSingle per karyawan, semua diikat batch_id yang sama
     * 4. Update total_employees di batch
     *
     * @param  array $batchData  — batch_name, incident_description, violation_cat_id, incident_date, evidence_path, created_by
     * @param  array $employeeIds — array of employee id
     * @return array{batch: WarningBatch, letters: WarningLetter[], skipped: array}
     */
    public function createBulk(array $batchData, array $employeeIds): array
    {
        return DB::transaction(function () use ($batchData, $employeeIds) {
            // Buat batch
            $batch = WarningBatch::create([
                'uid'                  => (string) Str::uuid(),
                'batch_name'           => $batchData['batch_name'],
                'incident_description' => $batchData['incident_description'] ?? null,
                'violation_cat_id'     => $batchData['violation_cat_id'],
                'incident_date'        => $batchData['incident_date'],
                'total_employees'      => 0,
                'evidence_path'        => $batchData['evidence_path'] ?? null,
                'created_by'           => $batchData['created_by'],
            ]);

            $letters = [];
            $skipped = []; // employee yang di-skip karena SP4 aktif

            foreach ($employeeIds as $employeeId) {
                try {
                    $letter = $this->createSingle([
                        'employee_id'      => $employeeId,
                        'violation_cat_id' => $batchData['violation_cat_id'],
                        'violation_date'   => $batchData['incident_date'],
                        'reason'           => $batchData['incident_description'] ?? '',
                        'created_by'       => $batchData['created_by'],
                        'batch_id'         => $batch->id,
                        'trigger_source'   => 'bulk',
                    ]);
                    $letters[] = $letter;
                } catch (RuntimeException $e) {
                    // SP4 aktif → karyawan ini di-skip, dicatat
                    $skipped[] = [
                        'employee_id' => $employeeId,
                        'reason'      => $e->getMessage(),
                    ];
                }
            }

            // Update total_employees di batch
            $batch->update(['total_employees' => count($letters)]);

            return ['batch' => $batch, 'letters' => $letters, 'skipped' => $skipped];
        });
    }

    // ─── Expiry & Recovery ────────────────────────────────────────────────────

    /**
     * Tandai semua SP yang sudah melewati valid_until sebagai 'expired'.
     * Cek recovery per karyawan: jika semua SP-nya expired → karyawan "pulih".
     *
     * Dipanggil oleh cron job harian.
     *
     * @return array{expired_count: int, recovered_employees: int[]}
     */
    public function expireOverdue(): array
    {
        $expiredCount        = 0;
        $recoveredEmployees  = [];

        DB::transaction(function () use (&$expiredCount, &$recoveredEmployees) {
            // Ambil semua letter yang harus di-expire
            $toExpire = WarningLetter::whereNotIn('status', ['expired', 'rejected', 'draft'])
                ->where('valid_until', '<', now()->toDateString())
                ->get();

            foreach ($toExpire as $letter) {
                $letter->update(['status' => 'expired']);
                $expiredCount++;
            }

            // Cek recovery: karyawan yang sekarang tidak punya SP aktif lagi
            $affectedEmployeeIds = $toExpire->pluck('employee_id')->unique();

            foreach ($affectedEmployeeIds as $employeeId) {
                $hasActive = WarningLetter::where('employee_id', $employeeId)
                    ->whereNotIn('status', ['expired', 'rejected'])
                    ->where('valid_until', '>=', now()->toDateString())
                    ->exists();

                if (!$hasActive) {
                    $recoveredEmployees[] = $employeeId;
                    \Log::info("[WarningLetter] Employee #{$employeeId} telah pulih — semua SP telah expired. Pelanggaran berikutnya mulai dari SP1.");
                }
            }
        });

        return [
            'expired_count'        => $expiredCount,
            'recovered_employees'  => $recoveredEmployees,
        ];
    }
}
