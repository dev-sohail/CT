<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('command'); // artisan command, e.g. rules:run
            $table->string('cron_expression', 64); // standard 5-field cron
            $table->boolean('is_active')->default(true);
            $table->json('arguments')->nullable();
            $table->string('last_status', 16)->nullable(); // success|failed|never
            $table->text('last_output')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('scheduled_task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_task_id')->constrained('scheduled_tasks')->cascadeOnDelete();
            $table->string('status', 16);
            $table->text('output')->nullable();
            $table->timestamps();

            $table->index(['scheduled_task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_logs');
        Schema::dropIfExists('scheduled_tasks');
    }
};