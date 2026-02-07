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
            if (Schema::hasColumn('purchases', 'actual_quantity')) {
                $table->dropColumn('actual_quantity');
            }
            
            if (Schema::hasColumn('purchases', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
            
            // Tambah kolom received_at dan received_by untuk tracking penerimaan
            if (!Schema::hasColumn('purchases', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('approved_by');
            }
            
            if (!Schema::hasColumn('purchases', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->after('received_at');
            }
            
            // Ubah item_status untuk sederhana
            if (!Schema::hasColumn('purchases', 'item_status')) {
                $table->enum('item_status', ['pending', 'received', 'not_received'])
                      ->default('pending')
                      ->after('status');
            }
            
            // Foreign key untuk received_by
            if (Schema::hasColumn('purchases', 'received_by')) {
                $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Hapus foreign key
            if (Schema::hasColumn('purchases', 'received_by')) {
                $table->dropForeign(['received_by']);
            }
            
            // Hapus kolom baru
            $columnsToDrop = [
                'received_at',
                'received_by',
                'item_status'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Tambah kembali kolom yang dihapus
            if (!Schema::hasColumn('purchases', 'actual_quantity')) {
                $table->integer('actual_quantity')->nullable()->after('quantity');
            }
            
            if (!Schema::hasColumn('purchases', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->after('resi_number');
            }
        });
    }
};