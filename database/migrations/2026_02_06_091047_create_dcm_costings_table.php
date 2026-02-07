<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDcmCostingsTable extends Migration
{
    public function up()
    {
        Schema::create('dcm_costings', function (Blueprint $table) {
            $table->id();
            $table->string('po_number');
            $table->date('date');
            $table->enum('purchase_type', ['restock', 'new_item']);
            $table->string('item_name');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('freight', 15, 2)->default(0);
            $table->decimal('invoice_total', 15, 2);
            $table->string('department');
            $table->string('project_type');
            $table->string('project_name')->nullable();
            $table->string('job_order')->nullable();
            $table->string('supplier');
            $table->string('pic');
            $table->string('tracking_number')->nullable();
            $table->string('resi_number')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('item_status', ['pending', 'received', 'not_received'])->default('pending');
            $table->text('note')->nullable();
            $table->text('finance_notes')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->string('received_by')->nullable();
            $table->foreignId('purchase_id')->constrained('indo_purchases')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('po_number');
            $table->index('date');
            $table->index('status');
            $table->index('purchase_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dcm_costings');
    }
}