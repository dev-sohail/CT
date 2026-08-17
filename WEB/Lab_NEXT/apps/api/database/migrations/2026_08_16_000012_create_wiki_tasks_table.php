<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('wiki_workspaces')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('done')->default(false);
            $table->date('due_date')->nullable();
            $table->string('priority')->nullable();
            $table->string('taskable_type')->nullable();
            $table->unsignedBigInteger('taskable_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['taskable_type', 'taskable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_tasks');
    }
};
