<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->dateTime('observed_at');
            $table->string('observation_type', 16)->default('symptom');
            $table->string('name', 128);
            $table->decimal('value', 10, 2)->nullable();
            $table->string('unit', 32)->nullable();
            $table->unsignedTinyInteger('severity')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'observed_at']);
            $table->index(['user_id', 'observation_type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_observations');
    }
};
