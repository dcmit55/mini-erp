<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Expand column (lama VARCHAR(10), nama baru s/d 15 karakter) ───────────────
        DB::statement("ALTER TABLE session_shifts MODIFY COLUMN type_of_shift VARCHAR(30) NOT NULL");

        $dept = DB::table('departments')->pluck('id', 'name');

        $costume      = $dept['DCM Costume']      ?? null;
        $mascot       = $dept['DCM Mascot']       ?? null;
        $animatronics = $dept['DCM Animatronics'] ?? null;
        $opstech      = $dept['OpsTech']          ?? null;
        $finance      = $dept['Finance']          ?? null;
        $logistic     = $dept['Logistic']         ?? null;
        $hrga         = $dept['HRGA']             ?? null;

        // Helper: rename old→new untuk satu record.
        // Jika new sudah ada (dari seeder parsial): hapus dulu, baru rename old.
        // Jika old sudah tidak ada (sudah di-rename sebelumnya): skip.
        $safeRename = function (?int $deptId, array $extra, string $oldName, string $newName) {
            $base = function () use ($deptId, $extra) {
                $q = DB::table('session_shifts');
                $deptId === null ? $q->whereNull('department_id') : $q->where('department_id', $deptId);
                foreach ($extra as $col => $val) {
                    $q->where($col, $val);
                }
                return $q;
            };
            if ($base()->where('type_of_shift', $oldName)->exists()) {
                $base()->where('type_of_shift', $newName)->delete();
                $base()->where('type_of_shift', $oldName)->update(['type_of_shift' => $newName]);
            }
        };

        // ── 2. Rename type_of_shift ───────────────────────────────────────────────────────

        $safeRename(null, [], 'A9',  'GENERAL');
        $safeRename(null, [], 'A9S', 'GENERAL-S');

        if ($costume) {
            $safeRename($costume, [], 'C8',  'COSTUME');
            $safeRename($costume, [], 'C8S', 'COSTUME-S');
            $safeRename($costume, [], 'A9',  'COSTUME-ADMIN');
            $safeRename($costume, [], 'A9S', 'COSTUME-ADMIN-S');
        }

        if ($mascot) {
            $wni = ['for_wna' => false];
            $safeRename($mascot, $wni, 'A9',  'MASCOT-WNI');
            $safeRename($mascot, $wni, 'A9S', 'MASCOT-WNI-S');
            $safeRename($mascot, $wni, 'M10', 'MASCOT-WNI-10');
            $safeRename($mascot, $wni, 'M10S','MASCOT-WNI-10-S');
            $safeRename($mascot, $wni, 'B9',  'MASCOT-WNI-13');
            $safeRename($mascot, $wni, 'B9S', 'MASCOT-WNI-13-S');

            $wna = ['for_wna' => true];
            $safeRename($mascot, $wna, 'A12',  'MASCOT-WNA');
            $safeRename($mascot, $wna, 'A12S', 'MASCOT-WNA-S');
            $safeRename($mascot, $wna, 'B12',  'MASCOT-WNA-10');
            $safeRename($mascot, $wna, 'B12S', 'MASCOT-WNA-10-S');
        }

        if ($animatronics) {
            $safeRename($animatronics, [], 'A9',  'GENERAL');
            $safeRename($animatronics, [], 'A9S', 'GENERAL-S');
        }

        if ($opstech) {
            $safeRename($opstech, [], 'A9',  'GENERAL');
            $safeRename($opstech, [], 'A9S', 'GENERAL-S');
        }

        if ($logistic) {
            $safeRename($logistic, [], 'A9',   'LOGISTIC');
            $safeRename($logistic, [], 'A9S',  'LOGISTIC-S');
            $safeRename($logistic, [], 'S10',  'LOGISTIC-10');
            $safeRename($logistic, [], 'S10S', 'LOGISTIC-10-S');
            $safeRename($logistic, [], 'S13',  'LOGISTIC-13');
            $safeRename($logistic, [], 'S13S', 'LOGISTIC-13-S');
        }

        if ($hrga) {
            $safeRename($hrga, [], 'CH6',  'CHEF');
            $safeRename($hrga, [], 'CH6S', 'CHEF-S');
            $safeRename($hrga, [], 'SEC',  'SECURITY');
            $safeRename($hrga, [], 'SECS', 'SECURITY-S');
            $safeRename($hrga, [], 'CS',   'CLEANING');
            $safeRename($hrga, [], 'CSS',  'CLEANING-S');
        }

        if ($finance) {
            $safeRename($finance, [], 'EM',  'FINANCE-EM');
            $safeRename($finance, [], 'EMS', 'FINANCE-EM-S');
        }

        // ── 3. Bersihkan duplikat dan record salah dept ───────────────────────────────────

        // Hapus duplikat null-dept GENERAL/GENERAL-S
        // (MySQL NULL unik memungkinkan >1 row; pertahankan ID terkecil)
        foreach (['GENERAL', 'GENERAL-S'] as $name) {
            $minId = DB::table('session_shifts')
                ->whereNull('department_id')->where('type_of_shift', $name)->min('id');
            if ($minId) {
                DB::table('session_shifts')
                    ->whereNull('department_id')->where('type_of_shift', $name)
                    ->where('id', '!=', $minId)->delete();
            }
        }

        // Hapus null-dept LOGISTIC-* (bug: dept 'Store' tidak ada → $store=null →
        // seeder membuat record ini sebagai default padahal seharusnya dept-spesifik)
        DB::table('session_shifts')
            ->whereNull('department_id')
            ->whereIn('type_of_shift', [
                'LOGISTIC', 'LOGISTIC-S',
                'LOGISTIC-10', 'LOGISTIC-10-S',
                'LOGISTIC-13', 'LOGISTIC-13-S',
            ])
            ->delete();

        // ── 4. Konsolidasi Saturday: hapus semua -S yang identik dengan GENERAL-S ─────────
        // Semua Saturday = 08:00–13:00. Step-4 fallback di detectFromClockIn()
        // sudah otomatis jatuh ke null-dept GENERAL-S jika tidak ada record dept spesifik.
        // Pengecualian yang DIPERTAHANKAN:
        //   MASCOT-WNA-S  — WNA tidak bisa fallback ke GENERAL-S (for_wna=false)
        //   CHEF-S        — jam berbeda (06:00–12:00)
        //   FINANCE-EM-S  — jam berbeda + employee-specific

        if ($costume) {
            DB::table('session_shifts')->where('department_id', $costume)
                ->whereIn('type_of_shift', ['COSTUME-S', 'COSTUME-ADMIN-S'])->delete();
        }

        if ($mascot) {
            DB::table('session_shifts')->where('department_id', $mascot)->where('for_wna', false)
                ->whereIn('type_of_shift', [
                    'MASCOT-WNI-S', 'MASCOT-WNI-10-S', 'MASCOT-WNI-13-S',
                    'S10', 'S10S', 'S13', 'S13S',
                ])->delete();

            // MASCOT-WNA-S & MASCOT-WNA-10-S: step-4 fallback tidak filter for_wna
            // sehingga WNA bisa langsung fallback ke null-dept GENERAL-S.
            DB::table('session_shifts')->where('department_id', $mascot)->where('for_wna', true)
                ->whereIn('type_of_shift', ['MASCOT-WNA-S', 'MASCOT-WNA-10-S'])->delete();
        }

        if ($animatronics) {
            DB::table('session_shifts')->where('department_id', $animatronics)
                ->where('type_of_shift', 'GENERAL-S')->delete();
        }

        if ($opstech) {
            DB::table('session_shifts')->where('department_id', $opstech)
                ->where('type_of_shift', 'GENERAL-S')->delete();
        }

        if ($logistic) {
            DB::table('session_shifts')->where('department_id', $logistic)
                ->whereIn('type_of_shift', ['LOGISTIC-S', 'LOGISTIC-10-S', 'LOGISTIC-13-S'])->delete();
        }

        if ($hrga) {
            DB::table('session_shifts')->where('department_id', $hrga)
                ->whereIn('type_of_shift', ['SECURITY-S', 'CLEANING-S'])->delete();
        }

        // ── 5. Tambah default_shift_id ke employees ───────────────────────────────────────
        if (!Schema::hasColumn('employees', 'default_shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('default_shift_id')->nullable()->after('department_id');
                $table->foreign('default_shift_id')
                    ->references('id')->on('session_shifts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'default_shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['default_shift_id']);
                $table->dropColumn('default_shift_id');
            });
        }

        $dept = DB::table('departments')->pluck('id', 'name');

        $costume      = $dept['DCM Costume']      ?? null;
        $mascot       = $dept['DCM Mascot']       ?? null;
        $animatronics = $dept['DCM Animatronics'] ?? null;
        $opstech      = $dept['OpsTech']          ?? null;
        $finance      = $dept['Finance']          ?? null;
        $logistic     = $dept['Logistic']         ?? null;
        $hrga         = $dept['HRGA']             ?? null;

        $safeRename = function (?int $deptId, array $extra, string $oldName, string $newName) {
            $base = function () use ($deptId, $extra) {
                $q = DB::table('session_shifts');
                $deptId === null ? $q->whereNull('department_id') : $q->where('department_id', $deptId);
                foreach ($extra as $col => $val) {
                    $q->where($col, $val);
                }
                return $q;
            };
            if ($base()->where('type_of_shift', $oldName)->exists()) {
                $base()->where('type_of_shift', $newName)->delete();
                $base()->where('type_of_shift', $oldName)->update(['type_of_shift' => $newName]);
            }
        };

        $safeRename(null, [], 'GENERAL',   'A9');
        $safeRename(null, [], 'GENERAL-S', 'A9S');

        if ($costume) {
            $safeRename($costume, [], 'COSTUME',         'C8');
            $safeRename($costume, [], 'COSTUME-S',       'C8S');
            $safeRename($costume, [], 'COSTUME-ADMIN',   'A9');
            $safeRename($costume, [], 'COSTUME-ADMIN-S', 'A9S');
        }

        if ($mascot) {
            $wni = ['for_wna' => false];
            $safeRename($mascot, $wni, 'MASCOT-WNI',      'A9');
            $safeRename($mascot, $wni, 'MASCOT-WNI-S',    'A9S');
            $safeRename($mascot, $wni, 'MASCOT-WNI-10',   'M10');
            $safeRename($mascot, $wni, 'MASCOT-WNI-10-S', 'M10S');
            $safeRename($mascot, $wni, 'MASCOT-WNI-13',   'B9');
            $safeRename($mascot, $wni, 'MASCOT-WNI-13-S', 'B9S');

            $wna = ['for_wna' => true];
            $safeRename($mascot, $wna, 'MASCOT-WNA',    'A12');
            $safeRename($mascot, $wna, 'MASCOT-WNA-10', 'B12');
        }

        if ($animatronics) {
            $safeRename($animatronics, [], 'GENERAL',   'A9');
            $safeRename($animatronics, [], 'GENERAL-S', 'A9S');
        }

        if ($opstech) {
            $safeRename($opstech, [], 'GENERAL',   'A9');
            $safeRename($opstech, [], 'GENERAL-S', 'A9S');
        }

        if ($logistic) {
            $safeRename($logistic, [], 'LOGISTIC',      'A9');
            $safeRename($logistic, [], 'LOGISTIC-S',    'A9S');
            $safeRename($logistic, [], 'LOGISTIC-10',   'S10');
            $safeRename($logistic, [], 'LOGISTIC-10-S', 'S10S');
            $safeRename($logistic, [], 'LOGISTIC-13',   'S13');
            $safeRename($logistic, [], 'LOGISTIC-13-S', 'S13S');
        }

        if ($hrga) {
            $safeRename($hrga, [], 'CHEF',       'CH6');
            $safeRename($hrga, [], 'CHEF-S',     'CH6S');
            $safeRename($hrga, [], 'SECURITY',   'SEC');
            $safeRename($hrga, [], 'SECURITY-S', 'SECS');
            $safeRename($hrga, [], 'CLEANING',   'CS');
            $safeRename($hrga, [], 'CLEANING-S', 'CSS');
        }

        if ($finance) {
            $safeRename($finance, [], 'FINANCE-EM',   'EM');
            $safeRename($finance, [], 'FINANCE-EM-S', 'EMS');
        }

        // Column stays at VARCHAR(30) — reverting to VARCHAR(10) would break if any
        // human-readable names survived (e.g. records added by seeder after migration).
    }
};
