<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE qc_daily_items MODIFY COLUMN item_id VARCHAR(36) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE qc_daily_items MODIFY COLUMN item_id VARCHAR(10) NOT NULL");
    }
};
