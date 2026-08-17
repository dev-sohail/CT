<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('dosage', 64)->nullable();
            $table->string('form', 32)->nullable();
            $table->string('frequency', 32)->default('daily');
            $table->json('schedule')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('prescriber', 255)->nullable();
            $table->string('status', 16)->default('active');
            $table->text('instructions')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('medication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('medications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->dateTime('taken_at');
            $table->string('status', 16)->default('taken');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_logs');
        Schema::dropIfExists('medications');
    }
};
