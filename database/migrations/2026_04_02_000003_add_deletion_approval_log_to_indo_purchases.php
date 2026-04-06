<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('deletion_approved_by')->nullable()->after('deletion_requested_at');
            $table->timestamp('deletion_approved_at')->nullable()->after('deletion_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->dropColumn(['deletion_approved_by', 'deletion_approved_at']);
        });
    }
};
