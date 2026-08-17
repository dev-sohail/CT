<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('title')->nullable();
            $table->longText('body');
            $table->unsignedTinyInteger('mood')->nullable();
            $table->unsignedTinyInteger('energy')->nullable();
            $table->json('gratitude')->nullable();
            $table->json('wins')->nullable();
            $table->json('lessons')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_private')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'entry_date']);
            $table->index(['user_id', 'mood']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
