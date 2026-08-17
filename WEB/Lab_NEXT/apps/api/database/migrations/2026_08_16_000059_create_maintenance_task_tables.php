<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 64)->nullable();
            $table->string('location', 128)->nullable();
            $table->date('due_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->unsignedInteger('recurrence_days')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('status', 16)->default('planned');
            $table->string('priority', 16)->default('medium');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'due_on']);
            $table->index(['user_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('maintenance_tasks'); }
};
