<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('identity_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('identity_role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('identity_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('identity_permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
            $table->timestamps();
        });

        Schema::create('identity_model_has_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('identity_roles')->cascadeOnDelete();
            $table->morphs('model');
            $table->unique(['role_id', 'model_id', 'model_type'], 'model_has_roles_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_model_has_roles');
        Schema::dropIfExists('identity_role_permission');
        Schema::dropIfExists('identity_permissions');
        Schema::dropIfExists('identity_roles');
    }
};
