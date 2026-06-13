<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('thumbnail')->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->enum('status', ['draft', 'pending', 'published', 'rejected', 'unpublished'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->integer('duration_minutes')->default(0);
            $table->string('slug')->unique();
            $table->text('admin_feedback')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('featured');
            $table->index('level');
            $table->index('slug');
            $table->index('instructor_id');
            $table->index('category_id');
        });

        Schema::create('course_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->text('what_you_learn')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'locale']);
            $table->index('locale');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_translations');
        Schema::dropIfExists('courses');
    }
};
