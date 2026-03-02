<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('goods_movements', function (Blueprint $table) {
            // ✅ Tambah 2 kolom status baru
            $table->enum('sender_status', ['Pending', 'Prepared', 'Sent by Handcarry', 'Sent by Shipping', 'Checked', 'Received'])
                ->after('status')
                ->default('Pending');
                
            $table->enum('receiver_status', ['Pending', 'Prepared', 'Sent by Handcarry', 'Sent by Shipping', 'Checked', 'Received'])
                ->after('sender_status')
                ->default('Pending');
        });
    }

    public function down()
    {
        Schema::table('goods_movements', function (Blueprint $table) {
            $table->dropColumn(['sender_status', 'receiver_status']);
        });
    }
};
