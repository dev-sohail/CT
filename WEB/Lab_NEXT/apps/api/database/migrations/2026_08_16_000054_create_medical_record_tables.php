<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('record_type', 16)->default('visit');
            $table->date('record_date');
            $table->string('title');
            $table->string('provider', 255)->nullable();
            $table->string('facility', 255)->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('treatment')->nullable();
            $table->json('medications')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 16)->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'record_type']);
            $table->index(['user_id', 'record_date']);
            $table->index(['user_id', 'follow_up_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
