<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->date('spent_on');
            $table->string('description');
            $table->string('category', 64);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method', 32)->nullable();
            $table->boolean('recurring')->default(false);
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'spent_on']);
            $table->index(['user_id', 'category']);
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('category', 64);
            $table->decimal('amount', 12, 2);
            $table->string('period', 16)->default('monthly');
            $table->date('starts_on');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'category', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('expenses');
    }
};
