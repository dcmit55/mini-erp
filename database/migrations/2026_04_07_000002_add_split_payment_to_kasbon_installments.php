<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kasbon_installments', function (Blueprint $table) {
            $table->timestamp('pokok_paid_at')->nullable()->after('jumlah_biaya_admin');
            $table->foreignId('pokok_confirmed_by')->nullable()->constrained('users')->nullOnDelete()->after('pokok_paid_at');
            $table->timestamp('cash_paid_at')->nullable()->after('pokok_confirmed_by');
            $table->foreignId('cash_received_by')->nullable()->constrained('users')->nullOnDelete()->after('cash_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('kasbon_installments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pokok_confirmed_by');
            $table->dropConstrainedForeignId('cash_received_by');
            $table->dropColumn(['pokok_paid_at', 'cash_paid_at']);
        });
    }
};
