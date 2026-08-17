<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('category', 32)->default('general');
            $table->unsignedInteger('default_size_x')->default(2);
            $table->unsignedInteger('default_size_y')->default(1);
            $table->unsignedInteger('refresh_interval')->default(300); // seconds
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('user_widget_layout', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('widget_key', 64);
            $table->unsignedInteger('position_x')->default(0);
            $table->unsignedInteger('position_y')->default(0);
            $table->unsignedInteger('width')->default(2);
            $table->unsignedInteger('height')->default(1);
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('refresh_interval')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'widget_key']);
            $table->index(['user_id', 'position_y', 'position_x']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_widget_layout');
        Schema::dropIfExists('widget_registrations');
    }
};