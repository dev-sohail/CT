<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7)->nullable();
            $table->timestamps();
        });

        Schema::create('wiki_page_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('wiki_pages')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('wiki_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['page_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_tag');
        Schema::dropIfExists('wiki_tags');
    }
};
