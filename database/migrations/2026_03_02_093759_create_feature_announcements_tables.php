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
        // Table untuk feature announcements
        Schema::create('feature_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('version')->nullable(); // e.g., "2.12.0"
            $table->enum('priority', ['info', 'important', 'critical'])->default('info');
            $table->json('target_roles')->nullable(); // ['super_admin', 'admin_logistic']
            $table->json('target_user_ids')->nullable(); // [1, 2, 3]
            $table->boolean('is_active')->default(true);
            $table->timestamp('show_from')->nullable();
            $table->timestamp('show_until')->nullable();
            $table->timestamps();
        });

        // Table untuk tracking siapa yang sudah read
        Schema::create('feature_announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('feature_announcements')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_announcement_reads');
        Schema::dropIfExists('feature_announcements');
    }
};
