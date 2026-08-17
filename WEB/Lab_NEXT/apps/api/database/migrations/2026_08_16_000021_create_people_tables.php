<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->date('birthday')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('relationship_type', 32)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'name']);
            $table->index(['user_id', 'relationship_type']);
        });

        Schema::create('contact_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('type', 32); // email, phone, whatsapp, telegram, etc.
            $table->string('value');
            $table->string('label')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['person_id', 'type']);
        });

        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('related_person_id')->constrained('people')->cascadeOnDelete();
            $table->string('type', 32); // spouse, parent, child, sibling, friend, colleague, manager, reports_to
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['person_id', 'related_person_id', 'type']);
            $table->index(['user_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationships');
        Schema::dropIfExists('contact_methods');
        Schema::dropIfExists('people');
    }
};