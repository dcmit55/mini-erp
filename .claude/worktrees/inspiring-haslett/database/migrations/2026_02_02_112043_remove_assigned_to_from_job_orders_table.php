<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Hapus foreign key constraint dulu
            $table->dropForeign(['assigned_to']);
            
            // Hapus kolom assigned_to
            $table->dropColumn('assigned_to');
        });
    }

    public function down()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Tambah kembali kolom jika rollback
            $table->unsignedBigInteger('assigned_to')->nullable()->after('department_id');
            
            // Tambah foreign key
            $table->foreign('assigned_to')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }
};