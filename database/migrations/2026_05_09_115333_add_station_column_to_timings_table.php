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
        Schema::table('timings', function (Blueprint $table) {
            // Menambahkan kolom station dengan enum
            $table->enum('station', ['office', 'cutting', 'sewing', 'finishing'])
                  ->nullable() // boleh null, sesuai tabel asli banyak kolom nullable
                  ->after('step'); // letakkan setelah kolom step (opsional)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            // Hapus kolom station jika rollback
            $table->dropColumn('station');
        });
    }
};