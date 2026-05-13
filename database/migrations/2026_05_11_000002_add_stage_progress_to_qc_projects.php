<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_projects', function (Blueprint $table) {
            $table->json('stage_progress')->nullable()->after('packing_config');
        });
    }

    public function down(): void
    {
        Schema::table('qc_projects', function (Blueprint $table) {
            $table->dropColumn('stage_progress');
        });
    }
};
