<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_snippets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('language', 32)->default('text');
            $table->longText('code');
            $table->json('tags')->nullable();
            $table->string('package_type', 16)->nullable(); // composer|npm
            $table->string('package_name')->nullable();
            $table->string('package_version')->nullable();
            $table->boolean('favorite')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'language']);
            $table->index(['user_id', 'favorite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_snippets');
    }
};