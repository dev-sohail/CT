<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devops_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('record_type', 32);
            $table->string('name');
            $table->string('status', 24)->default('active');
            $table->string('environment')->nullable();
            $table->string('url')->nullable();
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'record_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('devops_records'); }
};
