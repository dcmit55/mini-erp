<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table
                ->enum('employment_type', ['PKWT', 'PKWTT', 'Daily Worker', 'Probation'])
                ->nullable()
                ->default(null)
                ->after('name');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });
    }
};
