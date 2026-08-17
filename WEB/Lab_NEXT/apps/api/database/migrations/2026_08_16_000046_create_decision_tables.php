<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('title');
            $table->text('context')->nullable();
            $table->json('options')->nullable();
            $table->text('decision')->nullable();
            $table->text('rationale')->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('status', 16)->default('open');
            $table->date('decided_at')->nullable();
            $table->date('review_at')->nullable();
            $table->text('outcome')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'review_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
