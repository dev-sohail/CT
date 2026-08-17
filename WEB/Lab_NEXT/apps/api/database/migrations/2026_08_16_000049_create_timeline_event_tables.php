<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->date('event_date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 32)->default('event');
            $table->string('category', 64)->nullable();
            $table->unsignedTinyInteger('significance')->default(3);
            $table->string('location', 255)->nullable();
            $table->json('people')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_date']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};
