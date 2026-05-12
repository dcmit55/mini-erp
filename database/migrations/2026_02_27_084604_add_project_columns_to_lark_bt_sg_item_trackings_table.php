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
        Schema::table('lark_bt_sg_item_trackings', function (Blueprint $table) {
            // Column untuk menyimpan raw JSON dari Lark "Link Project"
            $table->json('project_lark')->nullable()->after('sgd_cost');

            // Column untuk foreign key ke table projects (relasi ke ERP)
            $table->unsignedBigInteger('project_id')->nullable()->after('project_lark');

            // Foreign key constraint
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_bt_sg_item_trackings', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_lark', 'project_id']);
        });
    }
};
