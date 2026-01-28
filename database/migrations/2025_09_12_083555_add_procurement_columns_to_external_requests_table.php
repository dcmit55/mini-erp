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
        Schema::table('external_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('project_id');
            $table->decimal('price_per_unit', 15, 2)->nullable()->after('supplier_id');
            $table->unsignedBigInteger('currency_id')->nullable()->after('price_per_unit');
            $table
                ->enum('approval_status', ['Pending', 'Approved', 'Decline'])
                ->nullable()
                ->after('currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('external_requests', function (Blueprint $table) {
            $table->dropColumn(['supplier_id', 'price_per_unit', 'currency_id', 'approval_status']);
        });
    }
};
