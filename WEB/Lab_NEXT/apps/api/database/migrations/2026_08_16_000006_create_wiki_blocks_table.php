<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('wiki_pages')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('wiki_blocks')->nullOnDelete();
            $table->string('type');
            $table->text('content')->nullable();
            $table->json('meta')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['page_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_blocks');
    }
};
