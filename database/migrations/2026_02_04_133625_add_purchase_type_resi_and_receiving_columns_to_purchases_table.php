<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Purchase Type - untuk membedakan restock atau new item
            if (!Schema::hasColumn('purchases', 'purchase_type')) {
                $table->enum('purchase_type', ['restock', 'new_item'])->default('restock')->after('date');
            }
            
            // Nama item baru (jika purchase_type = 'new_item')
            if (!Schema::hasColumn('purchases', 'new_item_name')) {
                $table->string('new_item_name')->nullable()->after('material_id');
            }
            
            // Deskripsi item baru
            if (!Schema::hasColumn('purchases', 'new_item_description')) {
                $table->text('new_item_description')->nullable()->after('new_item_name');
            }
            
            // Quantity aktual saat barang diterima
            if (!Schema::hasColumn('purchases', 'actual_quantity')) {
                $table->integer('actual_quantity')->nullable()->after('quantity');
            }
            
            // Flag untuk offline order
            if (!Schema::hasColumn('purchases', 'is_offline_order')) {
                $table->boolean('is_offline_order')->default(false)->after('supplier_id');
            }
            
            // No. Kwitansi (receipt number)
            if (!Schema::hasColumn('purchases', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->after('tracking_number');
            }
            
            // No. Resi (untuk tracking pengiriman)
            if (!Schema::hasColumn('purchases', 'resi_number')) {
                $table->string('resi_number')->nullable()->after('receipt_number');
            }
            
            // Catatan saat barang diterima
            if (!Schema::hasColumn('purchases', 'received_notes')) {
                $table->text('received_notes')->nullable()->after('finance_notes');
            }
            
            // Timestamp barang diterima
            if (!Schema::hasColumn('purchases', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('approved_by');
            }
            
            // User yang menerima barang
            if (!Schema::hasColumn('purchases', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->after('received_at');
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
            // Drop foreign key
            $table->dropForeign(['received_by']);
            
            // Drop kolom yang ditambahkan
            $columns = [
                'purchase_type',
                'new_item_name',
                'new_item_description',
                'actual_quantity',
                'is_offline_order',
                'receipt_number',
                'resi_number',
                'received_notes',
                'received_at',
                'received_by'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};