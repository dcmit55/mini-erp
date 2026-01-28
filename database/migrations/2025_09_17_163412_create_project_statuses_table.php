<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_status_id')->nullable()->after('department_id')->constrained('project_statuses');
        });
    }
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_status_id']);
            $table->dropColumn('project_status_id');
        });
        Schema::dropIfExists('project_statuses');
    }
};
