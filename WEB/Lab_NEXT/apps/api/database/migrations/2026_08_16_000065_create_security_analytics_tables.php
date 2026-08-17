<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_analytics_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('record_type', 24);
            $table->string('name');
            $table->string('status', 24)->default('active');
            $table->decimal('value', 14, 2)->nullable();
            $table->dateTime('recorded_at')->nullable();
            $table->text('description')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'record_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('security_analytics_records'); }
};
