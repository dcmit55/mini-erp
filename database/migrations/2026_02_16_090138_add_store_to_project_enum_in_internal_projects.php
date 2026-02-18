<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // MySQL: Mengubah kolom project menjadi ENUM dengan nilai baru
        DB::statement("ALTER TABLE internal_projects MODIFY project ENUM('Office','Machine','Testing','Facilities','Store') NOT NULL DEFAULT 'Office'");
    }

    public function down()
    {
        // Kembalikan ke ENUM lama (tanpa 'Store')
        DB::statement("ALTER TABLE internal_projects MODIFY project ENUM('Office','Machine','Testing','Facilities') NOT NULL DEFAULT 'Office'");
    }
};