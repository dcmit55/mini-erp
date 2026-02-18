<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // STEP 1: Hapus foreign key constraint jika ada
        try {
            Schema::table('internal_projects', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
            });
        } catch (\Exception $e) {
            // Ignore jika tidak ada foreign key
        }

        // STEP 2: Hapus kolom jika sudah ada
        if (Schema::hasColumn('internal_projects', 'department_id')) {
            Schema::table('internal_projects', function (Blueprint $table) {
                $table->dropColumn('department_id');
            });
        }

        // STEP 3: Tambah kolom baru
        Schema::table('internal_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')
                  ->after('department')
                  ->default(24);
        });

        // STEP 4: Update semua data
        DB::table('internal_projects')->update(['department_id' => 24]);
    }

    public function down()
    {
        Schema::table('internal_projects', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });
    }
};