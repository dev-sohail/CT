<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('title');
            $table->string('subject')->nullable();
            $table->string('url')->nullable();
            $table->string('provider')->nullable();
            $table->string('status', 16)->default('not_started'); // not_started|in_progress|completed|paused
            $table->unsignedInteger('hours_target')->nullable();
            $table->decimal('hours_spent', 8, 2)->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};