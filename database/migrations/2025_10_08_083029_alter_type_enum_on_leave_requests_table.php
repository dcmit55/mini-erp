<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        //enum type
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'type')) {
                $table->enum('type', ['ANNUAL', 'SICK', 'MATERNITY', 'MARRIAGE', 'BEREAVEMENT', 'STUDY', 'OTHER', 'UNPAID', 'OFFICIAL'])->default('ANNUAL');
            }
        });
    }

    public function down()
    {
        // Kembalikan ke enum sebelumnya (ubah sesuai enum lama Anda)
        \DB::statement("ALTER TABLE leave_requests MODIFY COLUMN type ENUM('annual','sick','other') NOT NULL DEFAULT 'annual'");
    }
};
