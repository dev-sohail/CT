<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('url');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('saved_content')->nullable(); // full-text local archive
            $table->string('status', 16)->default('unread'); // unread|reading|read|archived
            $table->string('favicon_url')->nullable();
            $table->string('domain')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};