<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectTypeToPurchasesTable extends Migration
{
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Tambah kolom untuk project type
            $table->enum('project_type', ['client', 'internal'])->default('client')->after('department_id');
            
            // Ubah project_id menjadi nullable karena bisa dari internal project
            $table->foreignId('project_id')->nullable()->change();
            
            // Tambah kolom untuk internal_project_id
            $table->string('internal_project_id', 50)->nullable()->after('project_id');
            
            // Foreign key untuk internal_projects
            $table->foreign('internal_project_id')->references('id')->on('internal_projects');
        });
    }

    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['internal_project_id']);
            $table->dropColumn(['project_type', 'internal_project_id']);
        });
    }
}