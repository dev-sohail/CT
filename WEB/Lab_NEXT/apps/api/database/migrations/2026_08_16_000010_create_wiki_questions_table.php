<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('wiki_workspaces')->cascadeOnDelete();
            $table->string('title');
            $table->text('answer')->nullable();
            $table->string('status')->default('draft');
            $table->string('difficulty')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_questions');
    }
};
