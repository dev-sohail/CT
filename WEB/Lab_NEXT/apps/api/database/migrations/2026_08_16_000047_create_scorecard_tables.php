<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scorecard_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 64)->nullable();
            $table->string('unit', 32)->nullable();
            $table->decimal('target', 12, 2)->nullable();
            $table->string('direction', 8)->default('higher');
            $table->string('frequency', 16)->default('daily');
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'active']);
        });

        Schema::create('scorecard_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metric_id')->constrained('scorecard_metrics')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->date('measured_on');
            $table->decimal('value', 12, 2);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['metric_id', 'measured_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorecard_measurements');
        Schema::dropIfExists('scorecard_metrics');
    }
};
