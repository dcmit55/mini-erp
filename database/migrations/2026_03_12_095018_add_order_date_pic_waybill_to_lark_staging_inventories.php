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
        Schema::table('lark_staging_inventories', function (Blueprint $table) {
            // Order date from Lark (e.g. '05/02/2026')
            $table->string('order_date', 50)->nullable()->after('supplier_lark')->comment('Order Date field from Lark (e.g. 05/02/2026)');
            // PIC (Person In Charge) from Lark (e.g. 'Willing')
            $table->string('pic', 255)->nullable()->after('order_date')->comment('PIC field from Lark');
            // International Waybill number from Lark (e.g. 'SF0258960029756')
            $table->string('international_waybill', 255)->nullable()->after('pic')->comment('International Waybill field from Lark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_staging_inventories', function (Blueprint $table) {
            $table->dropColumn(['order_date', 'pic', 'international_waybill']);
        });
    }
};
