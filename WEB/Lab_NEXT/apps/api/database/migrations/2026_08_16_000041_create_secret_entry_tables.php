<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secret_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 64)->default('general');
            $table->string('url', 512)->nullable();
            $table->string('username', 255)->nullable();
            $table->text('password_encrypted')->nullable();
            $table->text('notes_encrypted')->nullable();
            $table->json('tags')->nullable();
            $table->string('totp_secret_encrypted', 512)->nullable();
            $table->boolean('totp_enabled')->default(false);
            $table->tinyInteger('strength_score')->nullable();
            $table->boolean('favorite')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'favorite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_entries');
    }
};