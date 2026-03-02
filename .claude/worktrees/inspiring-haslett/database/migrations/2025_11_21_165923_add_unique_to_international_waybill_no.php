<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('shippings', function (Blueprint $table) {
            // Ubah kolom dari nullable menjadi required
            $table->string('international_waybill_no')->nullable(false)->change();
            $table->string('freight_company')->nullable(false)->change();
            
            // Tambah unique constraint
            $table->unique('international_waybill_no', 'shippings_waybill_unique');
        });
    }

    public function down()
    {
        Schema::table('shippings', function (Blueprint $table) {
            // Hapus unique constraint
            $table->dropUnique('shippings_waybill_unique');
            
            // Kembalikan ke nullable
            $table->string('international_waybill_no')->nullable()->change();
            $table->string('freight_company')->nullable()->change();
        });
    }
};