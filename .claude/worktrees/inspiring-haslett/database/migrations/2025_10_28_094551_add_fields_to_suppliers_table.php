<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('supplier_code', 50)->nullable()->unique()->after('id');
            $table->string('contact_person', 20)->nullable()->after('name');
            $table->text('address')->nullable()->after('contact_person');
            $table->string('referral_link', 255)->nullable()->after('address');
            $table->string('lead_time_days', 10)->nullable()->after('referral_link');
            $table
                ->enum('status', ['active', 'inactive', 'blacklisted'])
                ->default('active')
                ->after('lead_time_days');
            $table->text('remark')->nullable()->after('status');
            $table->softDeletes()->after('updated_at');
        });

        // Add location_id foreign key
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->constrained('location_supplier')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['supplier_code', 'contact_person', 'address', 'referral_link', 'lead_time_days', 'status', 'remark', 'location_id']);
            $table->dropSoftDeletes();
        });
    }
};
