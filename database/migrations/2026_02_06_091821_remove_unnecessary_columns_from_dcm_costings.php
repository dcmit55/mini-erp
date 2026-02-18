<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUnnecessaryColumnsFromDcmCostings extends Migration
{
    public function up()
    {
        Schema::table('dcm_costings', function (Blueprint $table) {
            // Hapus kolom yang tidak perlu
            $table->dropColumn([
                'received_at',
                'received_by',
                'note'
            ]);
        });
    }

    public function down()
    {
        Schema::table('dcm_costings', function (Blueprint $table) {
            // Tambah kembali kolom jika rollback
            $table->dateTime('received_at')->nullable()->after('finance_notes');
            $table->string('received_by')->nullable()->after('received_at');
            $table->text('note')->nullable()->after('received_by');
        });
    }
}