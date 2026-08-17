<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('company', 128)->nullable();
            $table->string('category', 64)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_cycle', 16)->default('monthly'); // weekly|monthly|quarterly|yearly|one_time
            $table->date('started_at')->nullable();
            $table->date('next_billing_at')->nullable();
            $table->string('payment_method', 64)->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->string('status', 16)->default('active'); // active|cancelled|paused|expired
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'next_billing_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};