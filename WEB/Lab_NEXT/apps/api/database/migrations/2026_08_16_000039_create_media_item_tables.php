<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('type', 16); // book|movie|series|music|podcast|game|other
            $table->string('title');
            $table->string('creator', 255)->nullable();
            $table->integer('year')->nullable();
            $table->string('genre', 128)->nullable();
            $table->json('tags')->nullable();
            $table->tinyInteger('rating')->nullable(); // 1-10
            $table->string('status', 16)->default('want'); // want|in_progress|finished|abandoned
            $table->date('started_at')->nullable();
            $table->date('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};