<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fingerprint_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique(); 
            $table->string('cloud_id')->nullable(); 
            $table->timestamp('event_time')->nullable(); 
            $table->json('payload')->nullable(); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fingerprint_logs');
    }
};