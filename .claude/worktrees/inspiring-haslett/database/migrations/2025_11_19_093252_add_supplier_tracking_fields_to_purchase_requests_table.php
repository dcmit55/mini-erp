<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Tambah field untuk tracking supplier change
            $table->unsignedBigInteger('original_supplier_id')->nullable()->after('supplier_id');
            $table->text('supplier_change_reason')->nullable()->after('original_supplier_id');

            // Foreign key constraint
            $table->foreign('original_supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['original_supplier_id']);
            $table->dropColumn(['original_supplier_id', 'supplier_change_reason']);
        });
    }
};
