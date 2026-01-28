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
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Token name/description');
            $table->string('token', 64)->unique()->comment('Static API token');
            $table->boolean('is_active')->default(true);
            $table->string('allowed_ips')->nullable()->comment('Comma-separated IPs (optional)');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index('token');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};