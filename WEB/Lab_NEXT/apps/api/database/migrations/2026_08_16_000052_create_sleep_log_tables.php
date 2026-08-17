<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sleep_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->date('sleep_date');
            $table->dateTime('bedtime')->nullable();
            $table->dateTime('wake_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('quality')->nullable();
            $table->unsignedTinyInteger('interruptions')->default(0);
            $table->unsignedInteger('deep_minutes')->nullable();
            $table->unsignedInteger('light_minutes')->nullable();
            $table->unsignedInteger('rem_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'sleep_date']);
            $table->index(['user_id', 'quality']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sleep_logs');
    }
};
