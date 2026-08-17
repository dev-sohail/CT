<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_watcher_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('source_disk', 32)->default('local');
            $table->string('source_path'); // directory scanned for files
            $table->string('pattern')->nullable(); // glob, e.g. *.pdf or **/*.txt
            $table->string('action', 16)->default('move'); // move|copy|delete
            $table->string('destination_path')->nullable();
            $table->string('tag_keyword')->nullable(); // filter by filename keyword
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('file_watcher_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_watcher_rule_id')->constrained('file_watcher_rules')->cascadeOnDelete();
            $table->string('filename');
            $table->string('status', 16); // processed|skipped|failed
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['file_watcher_rule_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_watcher_logs');
        Schema::dropIfExists('file_watcher_rules');
    }
};