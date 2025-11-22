<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // A. Add freight_method to shippings table
        Schema::table('shippings', function (Blueprint $table) {
            $table
                ->enum('freight_method', ['Sea Freight', 'Air Freight'])
                ->default('Sea Freight')
                ->after('freight_company')
                ->comment('Method of international shipping');

            $table->index('freight_method');
        });

        // B. Add extra cost fields to shipping_details table
        Schema::table('shipping_details', function (Blueprint $table) {
            $table->decimal('extra_cost', 15, 2)->default(0)->after('int_cost')->comment('Extra cost for oversized/overweight items (Air Freight)');

            $table->string('extra_cost_reason', 255)->nullable()->after('extra_cost')->comment('Reason for extra cost (e.g., dimension, weight)');

            $table->index('extra_cost');
        });

        // C. Add extra cost to goods_receive_details (for tracking)
        Schema::table('goods_receive_details', function (Blueprint $table) {
            $table->decimal('extra_cost', 15, 2)->default(0)->after('unit_price')->comment('Extra cost copied from shipping detail');

            $table->string('extra_cost_reason', 255)->nullable()->after('extra_cost')->comment('Reason for extra cost');
        });
    }

    public function down()
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropIndex(['freight_method']);
            $table->dropColumn('freight_method');
        });

        Schema::table('shipping_details', function (Blueprint $table) {
            $table->dropIndex(['extra_cost']);
            $table->dropColumn(['extra_cost', 'extra_cost_reason']);
        });

        Schema::table('goods_receive_details', function (Blueprint $table) {
            $table->dropColumn(['extra_cost', 'extra_cost_reason']);
        });
    }
};
