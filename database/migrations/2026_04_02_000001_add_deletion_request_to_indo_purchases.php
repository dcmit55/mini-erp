<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->string('deletion_reason')->nullable()->after('finance_notes');
            $table->unsignedBigInteger('deletion_requested_by')->nullable()->after('deletion_reason');
            $table->timestamp('deletion_requested_at')->nullable()->after('deletion_requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->dropColumn(['deletion_reason', 'deletion_requested_by', 'deletion_requested_at']);
        });
    }
};
