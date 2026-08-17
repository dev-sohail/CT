<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('frequency', 16)->default('daily');
            $table->unsignedInteger('target_count')->default(1);
            $table->json('days_of_week')->nullable();
            $table->string('color', 32)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'active']);
        });

        Schema::create('habit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained('habits')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->date('logged_for');
            $table->unsignedInteger('count')->default(1);
            $table->boolean('completed')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['habit_id', 'logged_for']);
            $table->index(['user_id', 'logged_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_logs');
        Schema::dropIfExists('habits');
    }
};
