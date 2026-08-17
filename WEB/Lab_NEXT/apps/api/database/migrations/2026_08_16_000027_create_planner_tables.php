<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('planner_items')->nullOnDelete();
            $table->string('scope', 16)->default('day'); // day|week|month|quarter|year
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 16)->default('pending'); // pending|in_progress|completed|cancelled
            $table->string('priority', 16)->default('medium'); // low|medium|high|urgent
            $table->string('recurrence_rule')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scope', 'due_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_items');
    }
};