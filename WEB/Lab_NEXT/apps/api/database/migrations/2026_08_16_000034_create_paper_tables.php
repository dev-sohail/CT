<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('title');
            $table->json('authors')->nullable();
            $table->string('year', 8)->nullable();
            $table->string('source')->nullable(); // journal, arxiv, conference...
            $table->string('url')->nullable();
            $table->string('file_path')->nullable();
            $table->text('abstract')->nullable();
            $table->string('status', 16)->default('unread'); // unread|reading|read|reviewed
            $table->tinyInteger('rating')->nullable(); // 1-5
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('paper_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->constrained('papers')->cascadeOnDelete();
            $table->unsignedInteger('page')->nullable();
            $table->text('text');
            $table->text('note')->nullable();
            $table->string('color', 16)->nullable();
            $table->timestamps();

            $table->index(['paper_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_annotations');
        Schema::dropIfExists('papers');
    }
};