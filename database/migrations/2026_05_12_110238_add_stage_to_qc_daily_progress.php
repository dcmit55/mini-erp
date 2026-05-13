<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('qc_daily_progress', 'stage')) {
            Schema::table('qc_daily_progress', function (Blueprint $table) {
                $table->string('stage', 20)->nullable()->after('qc_project_id');
            });
        }

        // MySQL won't drop an index used as backing index for a FK; drop FK first.
        DB::statement('ALTER TABLE qc_daily_progress DROP FOREIGN KEY qc_daily_progress_qc_project_id_foreign');
        DB::statement('ALTER TABLE qc_daily_progress DROP INDEX qc_daily_progress_qc_project_id_date_unique');
        DB::statement('ALTER TABLE qc_daily_progress ADD UNIQUE KEY qc_daily_progress_project_stage_date_unique (qc_project_id, stage, date)');
        DB::statement('ALTER TABLE qc_daily_progress ADD CONSTRAINT qc_daily_progress_qc_project_id_foreign FOREIGN KEY (qc_project_id) REFERENCES qc_projects (id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE qc_daily_progress DROP FOREIGN KEY qc_daily_progress_qc_project_id_foreign');
        DB::statement('ALTER TABLE qc_daily_progress DROP INDEX qc_daily_progress_project_stage_date_unique');
        DB::statement('ALTER TABLE qc_daily_progress ADD UNIQUE KEY qc_daily_progress_qc_project_id_date_unique (qc_project_id, date)');
        DB::statement('ALTER TABLE qc_daily_progress ADD CONSTRAINT qc_daily_progress_qc_project_id_foreign FOREIGN KEY (qc_project_id) REFERENCES qc_projects (id) ON DELETE CASCADE');

        Schema::table('qc_daily_progress', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};
