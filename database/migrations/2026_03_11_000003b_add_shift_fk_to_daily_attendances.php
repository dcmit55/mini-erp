<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan FK shift_id di daily_attendances → shifts.
 * Dipisah dari 000002 karena tabel shifts baru dibuat di 000003.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah FK sudah ada (bisa terjadi jika migration sebelumnya partial)
        $existingFks = array_column(
            \Illuminate\Support\Facades\DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'daily_attendances'
                  AND CONSTRAINT_NAME = 'daily_attendances_shift_id_foreign'
            "),
            'CONSTRAINT_NAME'
        );

        if (empty($existingFks)) {
            Schema::table('daily_attendances', function (Blueprint $table) {
                $table->foreign('shift_id')
                      ->references('id')
                      ->on('shifts')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex('idx_da_emp_date_status');
        });
    }
};
