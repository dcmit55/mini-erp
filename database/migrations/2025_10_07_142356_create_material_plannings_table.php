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
        Schema::create('material_plannings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->enum('order_type', ['material_req', 'purchase_req']);
            $table->string('material_name');
            $table->decimal('qty_needed', 10, 2);
            $table->foreignId('unit_id')->constrained('units');
            $table->date('eta_date');
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_plannings');
    }
};
