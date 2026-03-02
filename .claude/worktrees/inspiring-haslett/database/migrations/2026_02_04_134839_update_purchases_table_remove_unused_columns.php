<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Hapus kolom yang tidak perlu
            if (Schema::hasColumn('purchases', 'new_item_description')) {
                $table->dropColumn('new_item_description');
            }
            
            if (Schema::hasColumn('purchases', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
            
            if (Schema::hasColumn('purchases', 'received_notes')) {
                $table->dropColumn('received_notes');
            }
            
            // Tambah kolom baru untuk status check barang
            if (!Schema::hasColumn('purchases', 'item_status')) {
                $table->enum('item_status', ['pending_check', 'matched', 'not_matched'])
                      ->default('pending_check')
                      ->after('status');
            }
            
            if (!Schema::hasColumn('purchases', 'checked_at')) {
                $table->timestamp('checked_at')->nullable()->after('item_status');
            }
            
            if (!Schema::hasColumn('purchases', 'checked_by')) {
                $table->unsignedBigInteger('checked_by')->nullable()->after('checked_at');
            }
            
            // Foreign key untuk checked_by
            if (Schema::hasColumn('purchases', 'checked_by')) {
                $table->foreign('checked_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Ubah tipe data tracking_number dan resi_number agar boleh null
            if (Schema::hasColumn('purchases', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->change();
            }
            
            if (Schema::hasColumn('purchases', 'resi_number')) {
                $table->string('resi_number')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Hapus foreign key
            if (Schema::hasColumn('purchases', 'checked_by')) {
                $table->dropForeign(['checked_by']);
            }
            
            // Hapus kolom baru
            if (Schema::hasColumn('purchases', 'item_status')) {
                $table->dropColumn('item_status');
            }
            
            if (Schema::hasColumn('purchases', 'checked_at')) {
                $table->dropColumn('checked_at');
            }
            
            if (Schema::hasColumn('purchases', 'checked_by')) {
                $table->dropColumn('checked_by');
            }
            
            // Tambah kembali kolom yang dihapus
            if (!Schema::hasColumn('purchases', 'new_item_description')) {
                $table->text('new_item_description')->nullable()->after('new_item_name');
            }
            
            if (!Schema::hasColumn('purchases', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->after('resi_number');
            }
            
            if (!Schema::hasColumn('purchases', 'received_notes')) {
                $table->text('received_notes')->nullable()->after('finance_notes');
            }
        });
    }
};