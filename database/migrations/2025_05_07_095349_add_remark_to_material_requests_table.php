<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarkToMaterialRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->text('remark')->nullable()->after('department'); // Tambahkan kolom remark setelah department
        });
    }

    public function down()
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
}
