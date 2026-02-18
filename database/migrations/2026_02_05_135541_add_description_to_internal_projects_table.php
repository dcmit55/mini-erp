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
        Schema::table('internal_projects', function (Blueprint $table) {
            // Tambah kolom description setelah kolom job
            $table->text('description')->nullable()->after('job');
            
            // Jika perlu kolom lain, bisa ditambahkan di sini
            // $table->string('status')->default('active')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_projects', function (Blueprint $table) {
            // Hapus kolom description jika rollback
            $table->dropColumn('description');
        });
    }
};