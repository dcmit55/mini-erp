<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\Hr\ApprovalMatrix;
use App\Models\Hr\ApprovalTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApprovalService
{
    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Mulai alur approval untuk sebuah request.
     * Panggil ini saat request di-submit oleh employee.
     *
     * @param  string $module      e.g. 'leave', 'overtime'
     * @param  int    $referenceId id dari leave_requests / overtime_requests
     * @return ApprovalTransaction transaksi level pertama yang dibuat
     *
     * @throws RuntimeException jika approval sudah pernah diinisiasi atau matrix tidak ada
     */
    public function initiate(string $module, int $referenceId): ApprovalTransaction
    {
        return DB::transaction(function () use ($module, $referenceId) {
            // Guard: cegah double-initiate
            $alreadyExists = ApprovalTransaction::forReference($module, $referenceId)->exists();
            if ($alreadyExists) {
                throw new RuntimeException(
                    "Approval flow sudah pernah diinisiasi untuk {$module}#{$referenceId}."
                );
            }

            $firstLevel = ApprovalMatrix::where('module', $module)
                ->orderBy('level')
                ->first();

            if (!$firstLevel) {
                throw new RuntimeException(
                    "Approval matrix belum dikonfigurasi untuk module [{$module}]."
                );
            }

            return ApprovalTransaction::create([
                'module'       => $module,
                'reference_id' => $referenceId,
                'level'        => $firstLevel->level,
                'status'       => 'pending',
            ]);
        });
    }

    /**
     * Approve level yang sedang pending.
     * Jika masih ada level berikutnya, otomatis buat transaksi level berikutnya.
     * Jika ini level terakhir, return ['final' => true].
     *
     * @return array{
     *   final: bool,
     *   next_level: int|null,
     *   next_role: string|null,
     *   transaction: ApprovalTransaction
     * }
     *
     * @throws RuntimeException jika tidak ada pending atau user tidak berwenang
     */
    public function approve(string $module, int $referenceId, User $approver, ?string $remarks = null): array
    {
        return DB::transaction(function () use ($module, $referenceId, $approver, $remarks) {
            $transaction = $this->fetchPendingForUpdate($module, $referenceId);

            $this->assertAuthorized($transaction, $approver);

            $transaction->update([
                'approved_by' => $approver->id,
                'status'      => 'approved',
                'approved_at' => now(),
                'remarks'     => $remarks,
            ]);

            // Cek apakah ada level berikutnya
            $nextMatrix = ApprovalMatrix::where('module', $module)
                ->where('level', '>', $transaction->level)
                ->orderBy('level')
                ->first();

            if ($nextMatrix) {
                $nextTransaction = ApprovalTransaction::create([
                    'module'       => $module,
                    'reference_id' => $referenceId,
                    'level'        => $nextMatrix->level,
                    'status'       => 'pending',
                ]);

                return [
                    'final'       => false,
                    'next_level'  => $nextMatrix->level,
                    'next_role'   => $nextMatrix->role,
                    'transaction' => $nextTransaction,
                ];
            }

            // Semua level sudah approved → final approved
            return [
                'final'       => true,
                'next_level'  => null,
                'next_role'   => null,
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Tolak level yang sedang pending. Menghentikan seluruh alur approval.
     *
     * @throws RuntimeException jika tidak ada pending atau user tidak berwenang
     */
    public function reject(string $module, int $referenceId, User $approver, ?string $remarks = null): ApprovalTransaction
    {
        return DB::transaction(function () use ($module, $referenceId, $approver, $remarks) {
            $transaction = $this->fetchPendingForUpdate($module, $referenceId);

            $this->assertAuthorized($transaction, $approver);

            $transaction->update([
                'approved_by' => $approver->id,
                'status'      => 'rejected',
                'approved_at' => now(),
                'remarks'     => $remarks,
            ]);

            return $transaction;
        });
    }

    // ─── Status Queries ───────────────────────────────────────────────────────

    /**
     * Cek apakah semua level sudah approved (final approved).
     * Gunakan ini di Payroll sebelum memproses data.
     */
    public function isFinalApproved(string $module, int $referenceId): bool
    {
        $totalLevels = ApprovalMatrix::totalLevels($module);

        if ($totalLevels === 0) {
            return false;
        }

        $approvedCount = ApprovalTransaction::forReference($module, $referenceId)
            ->approved()
            ->count();

        return $approvedCount === $totalLevels;
    }

    /**
     * Cek apakah ada level yang ditolak (flow berhenti).
     */
    public function isRejected(string $module, int $referenceId): bool
    {
        return ApprovalTransaction::forReference($module, $referenceId)
            ->rejected()
            ->exists();
    }

    /**
     * Ambil transaksi pending saat ini (tanpa lock).
     */
    public function getCurrentPending(string $module, int $referenceId): ?ApprovalTransaction
    {
        return ApprovalTransaction::forReference($module, $referenceId)
            ->pending()
            ->latest('level')
            ->first();
    }

    /**
     * Ambil seluruh audit trail untuk satu reference, urut per level.
     */
    public function getAuditTrail(string $module, int $referenceId): Collection
    {
        return ApprovalTransaction::with('approver')
            ->forReference($module, $referenceId)
            ->orderBy('level')
            ->get();
    }

    // ─── Payroll Safety Helpers ───────────────────────────────────────────────

    /**
     * Ambil reference_id yang sudah final approved untuk satu module.
     * Gunakan hasilnya untuk filter query payroll.
     *
     * Contoh penggunaan:
     *   $ids = $approvalService->getFinalApprovedIds('overtime');
     *   $records = OvertimeRequest::whereIn('id', $ids)->get();
     */
    public function getFinalApprovedIds(string $module): \Illuminate\Support\Collection
    {
        $totalLevels = ApprovalMatrix::totalLevels($module);

        if ($totalLevels === 0) {
            return collect();
        }

        return ApprovalTransaction::where('module', $module)
            ->where('status', 'approved')
            ->groupBy('reference_id')
            ->havingRaw('COUNT(*) = ?', [$totalLevels])
            ->pluck('reference_id');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Ambil transaksi pending dengan row lock untuk mencegah race condition.
     */
    private function fetchPendingForUpdate(string $module, int $referenceId): ApprovalTransaction
    {
        $transaction = ApprovalTransaction::forReference($module, $referenceId)
            ->pending()
            ->lockForUpdate()
            ->first();

        if (!$transaction) {
            throw new RuntimeException(
                "Tidak ada approval pending untuk {$module}#{$referenceId}."
            );
        }

        return $transaction;
    }

    /**
     * Validasi bahwa user berhak melakukan aksi pada level ini.
     * super_admin diizinkan bertindak di semua level.
     */
    private function assertAuthorized(ApprovalTransaction $transaction, User $approver): void
    {
        // super_admin melewati semua pembatasan role
        if ($approver->role === 'super_admin') {
            return;
        }

        $matrix = ApprovalMatrix::where('module', $transaction->module)
            ->where('level', $transaction->level)
            ->first();

        if (!$matrix) {
            throw new RuntimeException(
                "Konfigurasi approval matrix tidak ditemukan untuk module [{$transaction->module}] level [{$transaction->level}]."
            );
        }

        // Cek role utama dan semua delegate_roles
        $allowedRoles = $matrix->getAllowedRoles();
        if (!in_array($approver->role, $allowedRoles)) {
            throw new RuntimeException(
                "Role user [{$approver->role}] tidak diizinkan untuk level [{$transaction->level}]. "
                . "Diizinkan: [" . implode(', ', $allowedRoles) . "]."
            );
        }
    }
}
