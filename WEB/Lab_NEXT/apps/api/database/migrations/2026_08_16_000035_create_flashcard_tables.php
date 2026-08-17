<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'title']);
        });

        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained('decks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->text('question');
            $table->text('answer');
            $table->decimal('ease_factor', 4, 2)->default(2.50); // SM-2 EF
            $table->unsignedInteger('interval_days')->default(0); // current interval
            $table->unsignedInteger('repetitions')->default(0); // consecutive correct
            $table->dateTime('due_at')->nullable();
            $table->dateTime('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['deck_id', 'due_at']);
            $table->index(['user_id', 'due_at']);
        });

        Schema::create('flashcard_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flashcard_id')->constrained('flashcards')->cascadeOnDelete();
            $table->tinyInteger('quality'); // 0-5 SM-2 quality
            $table->timestamps();

            $table->index(['flashcard_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcard_reviews');
        Schema::dropIfExists('flashcards');
        Schema::dropIfExists('decks');
    }
};