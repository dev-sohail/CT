<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('wiki_workspaces')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('wiki_pages')->nullOnDelete();
            $table->string('type')->default('weekly');
            $table->string('status')->default('pending');
            $table->date('scheduled_for')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_reviews');
    }
};
