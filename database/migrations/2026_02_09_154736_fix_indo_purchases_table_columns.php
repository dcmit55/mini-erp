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
        Schema::table('indo_purchases', function (Blueprint $table) {
            // OPTION 1: Rename tracking_number menjadi resi_number
            $table->renameColumn('tracking_number', 'resi_number');
            
            // OPTION 2: Atau tambahkan kolom baru (pilih salah satu)
            // $table->string('resi_number')->nullable()->after('tracking_number');
            
            // Tambahkan kolom approved_by jika tidak ada
            if (!Schema::hasColumn('indo_purchases', 'approved_by')) {
                $table->bigInteger('approved_by')->unsigned()->nullable()->after('approved_at');
            }
            
            // Perbaiki enum item_status agar sesuai dengan model
            $table->enum('item_status', ['pending_check', 'matched', 'not_matched', 'pending', 'received', 'not_received'])
                  ->default('pending_check')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Untuk rename
            $table->renameColumn('resi_number', 'tracking_number');
            
            // Untuk hapus kolom (jika pakai option 2)
            // $table->dropColumn('resi_number');
            
            // Untuk hapus kolom approved_by
            if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            
            // Kembalikan enum item_status
            $table->enum('item_status', ['pending_check', 'matched', 'not_matched'])
                  ->default('pending_check')
                  ->change();
        });
    }
};