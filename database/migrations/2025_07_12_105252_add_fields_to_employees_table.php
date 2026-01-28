<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('department')->nullable()->after('position');
            $table->string('email')->nullable()->after('department');
            $table->string('phone')->nullable()->after('email');
            $table->date('hire_date')->nullable()->after('phone');
            $table->decimal('salary', 15, 2)->nullable()->after('hire_date');
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active')->after('salary');
            $table->text('notes')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['department', 'email', 'phone', 'hire_date', 'salary', 'status', 'notes']);
        });
    }
};
