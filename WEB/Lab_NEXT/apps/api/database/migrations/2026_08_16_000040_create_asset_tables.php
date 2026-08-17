<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 64)->nullable();
            $table->string('location', 128)->nullable();
            $table->string('qr_token', 64)->unique();
            $table->string('status', 16)->default('in_place'); // in_place|missing|loaned|disposed
            $table->decimal('value', 12, 2)->nullable();
            $table->date('purchased_at')->nullable();
            $table->date('warranty_until')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};