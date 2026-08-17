<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->date('eaten_on');
            $table->string('meal_type', 16)->default('other');
            $table->string('name');
            $table->decimal('calories', 8, 2)->nullable();
            $table->decimal('protein_grams', 8, 2)->nullable();
            $table->decimal('carbs_grams', 8, 2)->nullable();
            $table->decimal('fat_grams', 8, 2)->nullable();
            $table->json('ingredients')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'eaten_on']);
            $table->index(['user_id', 'meal_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
