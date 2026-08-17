<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('room', 128)->nullable();
            $table->string('status', 16)->default('planned');
            $table->string('priority', 16)->default('medium');
            $table->date('started_on')->nullable();
            $table->date('target_date')->nullable();
            $table->date('completed_on')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('spent', 12, 2)->default(0);
            $table->json('tasks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('home_projects'); }
};
