<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('record_type', 24);
            $table->string('name');
            $table->decimal('amount', 14, 2)->nullable();
            $table->decimal('target_amount', 14, 2)->nullable();
            $table->decimal('current_amount', 14, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status', 24)->default('active');
            $table->date('due_date')->nullable();
            $table->string('institution')->nullable();
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'record_type']);
            $table->index(['user_id', 'due_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('finance_records'); }
};
