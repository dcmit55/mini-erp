<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // Tambah destination ke shipping_details (per-item destination)
        Schema::table('shipping_details', function (Blueprint $table) {
            $table
                ->enum('destination', ['SG', 'BT', 'CN', 'MY', 'Other'])
                ->default('SG')
                ->after('int_cost')
                ->comment('Final destination for this item');

            $table->index('destination');
        });

        // Tambah destination ke goods_receive_details (untuk tracking consistency)
        Schema::table('goods_receive_details', function (Blueprint $table) {
            $table
                ->enum('destination', ['SG', 'BT', 'CN', 'MY', 'Other'])
                ->nullable()
                ->after('received_qty')
                ->comment('Destination copied from shipping');

            $table->index('destination');
        });
    }

    public function down()
    {
        Schema::table('shipping_details', function (Blueprint $table) {
            $table->dropIndex(['destination']);
            $table->dropColumn('destination');
        });

        Schema::table('goods_receive_details', function (Blueprint $table) {
            $table->dropIndex(['destination']);
            $table->dropColumn('destination');
        });
    }
};
