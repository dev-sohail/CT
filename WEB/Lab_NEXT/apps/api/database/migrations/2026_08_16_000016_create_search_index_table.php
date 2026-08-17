<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('searchable_type');
            $table->unsignedBigInteger('searchable_id');
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->unsignedTinyInteger('weight')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'searchable_type', 'searchable_id'], 'search_index_owner_unique');
            $table->index(['user_id', 'searchable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_index');
    }
};
