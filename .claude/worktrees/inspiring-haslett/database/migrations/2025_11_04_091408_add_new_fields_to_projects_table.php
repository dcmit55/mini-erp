<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Kolom stage untuk menyimpan production stage dari Lark
            if (!Schema::hasColumn('projects', 'stage')) {
                $table->string('stage')->nullable()->after('project_status_id');
            }
            
            // Kolom submission_form untuk link Submission Form dari Lark
            if (!Schema::hasColumn('projects', 'submission_form')) {
                $table->text('submission_form')->nullable()->after('stage');
            }
            
            // Kolom img untuk path gambar dari Lark WIP Images
            if (!Schema::hasColumn('projects', 'img')) {
                $table->string('img')->nullable()->after('submission_form');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['stage', 'submission_form', 'img']);
        });
    }
};