<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('trigger_type', 32); // which trigger feeds the context
            $table->json('trigger_config')->nullable();
            $table->string('conditions_logic', 8)->default('all'); // all|any
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('rules')->cascadeOnDelete();
            $table->string('type', 32); // comparison|date
            $table->string('field'); // dot notation into context
            $table->string('operator', 32);
            $table->json('value')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['rule_id', 'order']);
        });

        Schema::create('rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('rules')->cascadeOnDelete();
            $table->string('type', 32); // notify|audit|log
            $table->json('config')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['rule_id', 'order']);
        });

        Schema::create('rule_execution_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('rules')->cascadeOnDelete();
            $table->boolean('triggered')->default(false);
            $table->json('context')->nullable();
            $table->json('results')->nullable();
            $table->timestamps();

            $table->index(['rule_id', 'triggered']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_execution_log');
        Schema::dropIfExists('rule_actions');
        Schema::dropIfExists('rule_conditions');
        Schema::dropIfExists('rules');
    }
};