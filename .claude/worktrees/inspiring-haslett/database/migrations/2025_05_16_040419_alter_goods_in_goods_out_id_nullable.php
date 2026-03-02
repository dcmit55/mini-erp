<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Tambahkan ini

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE goods_in MODIFY goods_out_id BIGINT UNSIGNED NULL');
    }
    public function down()
    {
        DB::statement('ALTER TABLE goods_in MODIFY goods_out_id BIGINT UNSIGNED NOT NULL');
    }
};