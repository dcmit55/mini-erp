<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE qc_reject_logs MODIFY COLUMN daily_item_id VARCHAR(36) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE qc_reject_logs MODIFY COLUMN daily_item_id VARCHAR(10) NULL");
    }
};
