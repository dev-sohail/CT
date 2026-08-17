<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('wiki_workspaces')->cascadeOnDelete();
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('type')->default('article');
            $table->string('author')->nullable();
            $table->unsignedTinyInteger('credibility')->default(3);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_references');
    }
};
