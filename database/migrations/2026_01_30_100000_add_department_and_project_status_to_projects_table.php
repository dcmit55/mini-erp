<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            // Kolom department untuk menyimpan "Type of Project" dari Lark
            $table->string('department')->nullable()->after('name');

            // Kolom project_status untuk menyimpan "Batam Job Order Statuses" dari Lark
            // Bisa berisi multiple values (comma separated atau JSON)
            $table->text('project_status')->nullable()->after('stage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['department', 'project_status']);
        });
    }
};
