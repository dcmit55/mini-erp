<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('address')->nullable()->after('phone');
            $table
                ->enum('gender', ['male', 'female'])
                ->nullable()
                ->after('address');
            $table->string('ktp_id', 20)->nullable()->after('gender');
            $table->string('place_of_birth', 100)->nullable()->after('ktp_id');
            $table->date('date_of_birth')->nullable()->after('place_of_birth');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth']);
        });
    }
};
