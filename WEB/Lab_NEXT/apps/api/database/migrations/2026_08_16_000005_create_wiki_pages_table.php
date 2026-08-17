<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('wiki_sections')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('wiki_projects')->nullOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('icon')->nullable();
            $table->string('type')->default('page');
            $table->string('status')->default('active');
            $table->string('difficulty')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('pinned_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['section_id', 'sort_order']);
            $table->index('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_pages');
    }
};
