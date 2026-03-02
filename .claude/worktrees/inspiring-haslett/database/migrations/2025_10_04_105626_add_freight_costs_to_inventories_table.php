<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('unit_domestic_freight_cost', 15, 2)->nullable()->after('price');
            $table->decimal('unit_international_freight_cost', 15, 2)->nullable()->after('unit_domestic_freight_cost');
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['unit_domestic_freight_cost', 'unit_international_freight_cost']);
        });
    }
};
