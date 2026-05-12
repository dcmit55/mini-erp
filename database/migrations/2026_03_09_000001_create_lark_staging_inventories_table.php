<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * lark_staging_inventories adalah tabel staging untuk data purchase dari Lark
     * sebelum di-filter dan diapprove masuk ke tabel inventories.
     *
     * Flow baru:
     * Lark API → lark_staging_inventories (staging/review) → inventories (after approval)
     */
    public function up(): void
    {
        Schema::create('lark_staging_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('lark_record_id')->nullable()->comment('Lark record ID (source)');
            $table->string('name')->comment('Item name dari Lark (Item Requested)');
            $table->string('project_lark')->nullable()->comment('Link Project dari Lark');
            $table->decimal('quantity', 15, 2)->default(0)->comment('Quantity dari Lark');
            $table->string('unit')->nullable()->comment('Unit dari Lark');
            $table->decimal('price', 15, 2)->default(0)->comment('Cost Amount Per Unit dari Lark (RMB)');
            $table->unsignedBigInteger('currency_id')->nullable()->comment('Currency ID (default RMB = 6)');
            $table->string('supplier_lark')->nullable()->comment('Supplier Name dari Lark');
            $table->string('img')->nullable()->comment('URL gambar item dari Lark');
            $table->string('destination')->nullable()->comment('Destination dari Lark (e.g. BATAM)');
            $table->string('status')->nullable()->comment('Status dari Lark (e.g. Sent Out)');
            $table->string('dept_imported')->nullable()->comment('DEPT (IMPORTED) dari Lark');
            $table->text('source_record_ids')->nullable()->comment('Comma-separated Lark record IDs (untuk aggregated items)');
            $table->integer('source_record_count')->default(1)->comment('Jumlah source records yang diaggregasi');
            $table
                ->enum('review_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->comment('Status review sebelum masuk ke inventory');
            $table->text('review_note')->nullable()->comment('Catatan review dari admin');
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('User yang mereview');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('last_sync_at')->nullable()->comment('Timestamp saat sync dari Lark');
            $table->timestamps();

            $table->index('name');
            $table->index('review_status');
            $table->index('last_sync_at');
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lark_staging_inventories');
    }
};
