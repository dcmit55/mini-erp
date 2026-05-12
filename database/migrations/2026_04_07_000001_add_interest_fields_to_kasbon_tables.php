<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kasbon_requests', function (Blueprint $table) {
            $table->decimal('suku_bunga_persen', 5, 2)->default(2.00)->after('tenor_bulan');
            $table->decimal('biaya_admin', 15, 2)->default(50000)->after('suku_bunga_persen');
        });

        Schema::table('kasbon_installments', function (Blueprint $table) {
            $table->decimal('jumlah_pokok', 15, 2)->default(0)->after('jumlah_cicilan');
            $table->decimal('jumlah_bunga', 15, 2)->default(0)->after('jumlah_pokok');
            $table->decimal('jumlah_biaya_admin', 15, 2)->default(0)->after('jumlah_bunga');
        });
    }

    public function down(): void
    {
        Schema::table('kasbon_requests', function (Blueprint $table) {
            $table->dropColumn(['suku_bunga_persen', 'biaya_admin']);
        });

        Schema::table('kasbon_installments', function (Blueprint $table) {
            $table->dropColumn(['jumlah_pokok', 'jumlah_bunga', 'jumlah_biaya_admin']);
        });
    }
};
