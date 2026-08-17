<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('title');
            $table->string('issuer')->nullable();
            $table->string('status', 16)->default('planned'); // planned|in_progress|attained|expired
            $table->date('issued_at')->nullable();
            $table->date('expiry_at')->nullable();
            $table->string('credential_url')->nullable();
            $table->json('skills')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'expiry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};