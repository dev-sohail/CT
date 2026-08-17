<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('level', 16)->default('goal'); // life|goal|year|quarter|week
            $table->timestamp('start_at')->nullable();
            $table->timestamp('target_at')->nullable();
            $table->string('status', 16)->default('active'); // active|completed|paused|abandoned
            $table->decimal('progress', 5, 2)->default(0); // 0-100, rolled up from children/KRs
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'parent_id']);
        });

        Schema::create('key_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('current_value', 14, 4)->default(0);
            $table->decimal('target_value', 14, 4)->default(1);
            $table->string('unit', 32)->nullable();
            $table->string('status', 16)->default('on_track'); // on_track|at_risk|behind|done
            $table->decimal('progress', 5, 2)->default(0); // 0-100 computed
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['goal_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_results');
        Schema::dropIfExists('goals');
    }
};