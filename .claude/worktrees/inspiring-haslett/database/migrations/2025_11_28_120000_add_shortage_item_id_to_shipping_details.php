<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('shipping_details', function (Blueprint $table) {
            // Add shortage_item_id sebagai nullable foreign key
            $table->unsignedBigInteger('shortage_item_id')->nullable()->after('pre_shipping_id')->comment('Direct link to shortage item (bypass PR creation)');

            $table->foreign('shortage_item_id')->references('id')->on('shortage_items')->onDelete('set null');

            $table->index('shortage_item_id');
        });
    }

    public function down()
    {
        Schema::table('shipping_details', function (Blueprint $table) {
            $table->dropForeign(['shortage_item_id']);
            $table->dropIndex(['shortage_item_id']);
            $table->dropColumn('shortage_item_id');
        });
    }
};
