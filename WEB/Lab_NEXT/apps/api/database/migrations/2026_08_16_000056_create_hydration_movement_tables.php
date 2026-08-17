<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hydration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->dateTime('drank_at');
            $table->unsignedInteger('milliliters');
            $table->string('beverage', 32)->default('water');
            $table->timestamps();
            $table->index(['user_id', 'drank_at']);
        });

        Schema::create('movement_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->dateTime('moved_at');
            $table->unsignedInteger('minutes');
            $table->string('activity', 64)->default('walking');
            $table->unsignedInteger('steps')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_logs');
        Schema::dropIfExists('hydration_logs');
    }
};
