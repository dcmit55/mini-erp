<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropForeignKeyJobOrderIdFromMaterialRequests extends Migration
{
    public function up()
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Hapus foreign key constraint
            $table->dropForeign(['job_order_id']);
            
            // Opsional: hapus index jika ada (tidak wajib)
            // $table->dropIndex('material_requests_job_order_id_foreign');
        });
    }

    public function down()
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Kembalikan foreign key (HATI-HATI: hanya jika data sudah bersih)
            $table->foreign('job_order_id')
                  ->references('id')
                  ->on('job_orders')
                  ->onDelete('set null');
        });
    }
}