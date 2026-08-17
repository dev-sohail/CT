<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_travel_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('record_type', 32);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 24)->default('active');
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('unit', 32)->nullable();
            $table->json('details')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'record_type']);
            $table->index(['user_id', 'due_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('home_travel_records'); }
};
