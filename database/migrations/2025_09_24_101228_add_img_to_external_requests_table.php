<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('external_requests', function (Blueprint $table) {
            $table->string('img')->nullable()->after('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('external_requests', function (Blueprint $table) {
            $table->dropColumn('img');
        });
    }
};
