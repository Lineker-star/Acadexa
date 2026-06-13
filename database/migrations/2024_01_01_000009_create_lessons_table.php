<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->enum('type', ['video', 'text', 'quiz', 'assignment'])->default('video');
            $table->string('video_url')->nullable();
            $table->longText('content')->nullable();
            $table->string('attachment_path')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->boolean('is_free_preview')->default(false);
            $table->timestamps();

            $table->index(['module_id', 'order']);
            $table->index('type');
        });

        Schema::create('lesson_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->longText('content')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_translations');
        Schema::dropIfExists('lessons');
    }
};
