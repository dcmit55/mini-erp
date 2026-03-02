<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('shippings', function (Blueprint $table) {
            // Hapus unique constraint
            $table->dropUnique('shippings_waybill_unique');
        });
    }

    public function down()
    {
        Schema::table('shippings', function (Blueprint $table) {
            // Restore unique constraint jika rollback
            $table->unique('international_waybill_no', 'shippings_waybill_unique');
        });
    }
};
