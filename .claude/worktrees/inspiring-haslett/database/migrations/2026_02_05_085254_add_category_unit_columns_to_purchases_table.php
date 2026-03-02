<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryUnitColumnsToPurchasesTable extends Migration
{
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Tambah kolom category_id
            if (!Schema::hasColumn('purchases', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('job_order_id')->constrained('categories');
            }
            
            // Tambah kolom unit_id
            if (!Schema::hasColumn('purchases', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('category_id')->constrained('units');
            }
            
            // Hapus kolom yang tidak diperlukan
            if (Schema::hasColumn('purchases', 'received_notes')) {
                $table->dropColumn('received_notes');
            }
            
            if (Schema::hasColumn('purchases', 'new_item_description')) {
                $table->dropColumn('new_item_description');
            }
        });
    }

    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Hapus foreign constraints
            if (Schema::hasColumn('purchases', 'category_id')) {
                $table->dropForeign(['category_id']);
            }
            
            if (Schema::hasColumn('purchases', 'unit_id')) {
                $table->dropForeign(['unit_id']);
            }
            
            // Hapus kolom
            $table->dropColumn(['category_id', 'unit_id']);
            
            // Kembalikan kolom yang dihapus (jika perlu)
            $table->text('received_notes')->nullable();
            $table->text('new_item_description')->nullable();
        });
    }
}