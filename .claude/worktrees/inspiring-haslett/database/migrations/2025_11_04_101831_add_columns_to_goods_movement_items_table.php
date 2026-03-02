<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Tambah kolom baru jika belum ada
        if (!Schema::hasColumn('goods_movement_items', 'material_type')) {
            Schema::table('goods_movement_items', function (Blueprint $table) {
                $table->enum('material_type', ['Project', 'Goods Receive', 'Restock', 'New Material'])
                    ->after('goods_movement_id')
                    ->nullable();
            });
        }

        if (!Schema::hasColumn('goods_movement_items', 'project_id')) {
            Schema::table('goods_movement_items', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')
                    ->after('material_type')
                    ->nullable();
            });
        }

        if (!Schema::hasColumn('goods_movement_items', 'goods_receive_id')) {
            Schema::table('goods_movement_items', function (Blueprint $table) {
                $table->unsignedBigInteger('goods_receive_id')
                    ->after('project_id')
                    ->nullable();
            });
        }

        if (!Schema::hasColumn('goods_movement_items', 'goods_receive_detail_id')) {
            Schema::table('goods_movement_items', function (Blueprint $table) {
                $table->unsignedBigInteger('goods_receive_detail_id')
                    ->after('goods_receive_id')
                    ->nullable();
            });
        }

        if (!Schema::hasColumn('goods_movement_items', 'new_material_name')) {
            Schema::table('goods_movement_items', function (Blueprint $table) {
                $table->string('new_material_name')
                    ->after('inventory_id')
                    ->nullable();
            });
        }

        // Ubah inventory_id jadi nullable
        DB::statement('ALTER TABLE goods_movement_items MODIFY COLUMN inventory_id BIGINT UNSIGNED NULL');

        // FOREIGN KEY: JANGAN TAMBAHKAN LAGI JIKA SUDAH ADA DI MIGRASI AWAL!
        // Jika ingin mengubah tipe foreign key, drop dulu yang lama secara manual di MySQL:
        // ALTER TABLE goods_movement_items DROP FOREIGN KEY goods_movement_items_inventory_id_foreign;
        // Baru tambahkan foreign key baru jika memang perlu.
        // Untuk migration ini, cukup tambahkan kolom saja.
    }

    public function down()
    {
        Schema::table('goods_movement_items', function (Blueprint $table) {
            // Drop kolom yang ditambahkan
            $table->dropColumn([
                'material_type',
                'project_id',
                'goods_receive_id',
                'goods_receive_detail_id',
                'new_material_name'
            ]);
        });

        // Kembalikan inventory_id jadi NOT NULL
        DB::statement('ALTER TABLE goods_movement_items MODIFY COLUMN inventory_id BIGINT UNSIGNED NOT NULL');
    }
};