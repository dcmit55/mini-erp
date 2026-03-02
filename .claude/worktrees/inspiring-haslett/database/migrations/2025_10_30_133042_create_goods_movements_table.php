<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('goods_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->date('movement_date');

            $table->enum('movement_type', ['Handcarry', 'Courier'])->default('Handcarry');
            $table->string('movement_type_value')->nullable(); // e.g., "Basuki", "JNT"
            $table->enum('origin', ['SG', 'BT', 'CN', 'Other'])->default('Other');
            $table->enum('destination', ['SG', 'BT', 'CN', 'Other'])->default('Other');

            $table->string('sender');
            $table->string('receiver');
            $table->enum('status', ['Pending', 'Received'])->default('Pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goods_movement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_movement_id')->constrained('goods_movements')->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('pcs');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('goods_movement_items');
        Schema::dropIfExists('goods_movements');
    }
};
