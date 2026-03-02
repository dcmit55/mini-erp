<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterMaterialRequestsMakeProjectIdAndInternalProjectIdNullable extends Migration
{
    public function up()
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Ubah project_id menjadi nullable (boleh NULL)
            $table->unsignedBigInteger('project_id')->nullable()->change();

            // Ubah internal_project_id menjadi nullable (boleh NULL)
            $table->string('internal_project_id', 50)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Kembalikan ke NOT NULL (HATI-HATI: pastikan tidak ada data NULL)
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
            $table->string('internal_project_id', 50)->nullable(false)->change();
        });
    }
}