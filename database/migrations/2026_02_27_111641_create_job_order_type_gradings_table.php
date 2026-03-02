<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_order_type_gradings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('job_type_grade');
            $table->decimal('score', 8, 2)->default(0);
            $table->string('grading')->nullable();
            $table->string('job_type')->nullable();
            $table->string('product_sub_category')->nullable();
            $table->text('other_details')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('parent_items')->nullable();
            $table->string('lark_record_id')->unique();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_type_gradings');
    }
};