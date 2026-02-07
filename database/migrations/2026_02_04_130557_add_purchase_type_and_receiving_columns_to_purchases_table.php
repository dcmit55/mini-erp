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
        Schema::table('purchases', function (Blueprint $table) {
            // 1. Purchase Type - untuk membedakan restock atau new item
            $table->enum('purchase_type', ['restock', 'new_item'])->default('restock')->after('date');
            
            // 2. Kolom untuk new item (jika purchase_type = 'new_item')
            $table->string('new_item_name')->nullable()->after('material_id');
            $table->text('new_item_description')->nullable()->after('new_item_name');
            
            // 3. Kolom untuk actual quantity saat barang diterima
            $table->integer('actual_quantity')->nullable()->after('quantity');
            
            // 4. Kolom untuk receipt number (no kwitansi/penerimaan)
            $table->string('receipt_number')->nullable()->after('tracking_number');
            
            // 5. Kolom untuk notes saat barang diterima
            $table->text('received_notes')->nullable()->after('finance_notes');
            
            // 6. Kolom untuk tracking penerimaan barang
            $table->timestamp('received_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('received_by')->nullable()->after('received_at');
            
            // 7. Flag untuk offline order
            $table->boolean('is_offline_order')->default(false)->after('supplier_id');
            
            // Foreign key untuk received_by
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Hapus foreign key
            $table->dropForeign(['received_by']);
            
            // Hapus kolom yang ditambahkan
            $table->dropColumn([
                'purchase_type',
                'new_item_name',
                'new_item_description',
                'actual_quantity',
                'receipt_number',
                'received_notes',
                'received_at',
                'received_by',
                'is_offline_order'
            ]);
        });
    }
};