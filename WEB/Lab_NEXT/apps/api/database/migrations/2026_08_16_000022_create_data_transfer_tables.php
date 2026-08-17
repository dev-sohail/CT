<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('status', 16)->default('queued'); // queued|completed|failed
            $table->string('format', 8)->default('json'); // json|csv
            $table->json('domains')->nullable(); // which domains were included
            $table->string('file_path')->nullable();
            $table->json('item_counts')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('domain', 64);
            $table->string('format', 8)->default('json'); // json|csv
            $table->string('status', 16)->default('completed'); // completed|failed
            $table->json('item_counts')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
        Schema::dropIfExists('exports');
    }
};