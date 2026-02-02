<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * PENTING: Migration ini menambahkan:
     * 1. Kolom unit_id (foreign key ke table units)
     * 2. TIDAK menghapus kolom unit (varchar) yang lama
     *
     * Tujuan: Transisi bertahap dari unit VARCHAR ke unit_id INTEGER
     */
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Tambah kolom unit_id setelah kolom unit (jika ada) atau setelah quantity
            // nullable karena data lama belum punya unit_id
            $table->unsignedBigInteger('unit_id')->nullable()->after('quantity');

            // Foreign key constraint ke table units
            $table
                ->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null') // Jika unit dihapus, set null (tidak menghapus inventory)
                ->onUpdate('cascade'); // Jika id unit berubah, update otomatis
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Drop foreign key dulu
            $table->dropForeign(['unit_id']);

            // Kemudian drop column
            $table->dropColumn('unit_id');
        });
    }
};
