<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Ubah kolom saldo_cuti dari INT menjadi DECIMAL(5,2)
        // Format: XXX.XX (max 999.99 hari)
        DB::statement("
            ALTER TABLE employees
            MODIFY COLUMN saldo_cuti DECIMAL(5,2) DEFAULT 0.00
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Kembalikan ke INT
        DB::statement("
            ALTER TABLE employees
            MODIFY COLUMN saldo_cuti INT(11) DEFAULT 0
        ");
    }
};
