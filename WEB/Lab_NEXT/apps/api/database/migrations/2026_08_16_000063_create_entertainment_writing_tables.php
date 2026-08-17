<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entertainment_writing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('record_type', 32);
            $table->string('title');
            $table->string('creator')->nullable();
            $table->string('status', 24)->default('active');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->date('record_date')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'record_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('entertainment_writing_records'); }
};
