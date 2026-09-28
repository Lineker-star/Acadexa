<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assessment path and personal library:
 *  - a quiz belongs to a lesson (lesson quiz), a module (module exercise) or a course (final evaluation);
 *  - final-evaluation questions can be tagged with a module to measure knowledge per module;
 *  - attempts record their mode (diagnostic / standard / retake) to follow knowledge over time;
 *  - course books that students keep in their account library and read offline.
 * Additive only: existing quizzes stay lesson quizzes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('scope', 10)->default('lesson')->after('id');
            $table->foreignId('module_id')->nullable()->after('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->after('module_id')->constrained()->cascadeOnDelete();
            $table->index('scope');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->change();
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->foreignId('module_id')->nullable()->after('quiz_id')->constrained()->nullOnDelete();
            $table->text('explanation')->nullable()->after('type');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('mode', 12)->default('standard')->after('quiz_id');
            $table->unsignedSmallInteger('correct_count')->default(0)->after('score');
            $table->unsignedSmallInteger('total_questions')->default(0)->after('correct_count');
            $table->json('module_scores')->nullable()->after('answers');
            $table->index(['quiz_id', 'mode']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('reassess_reminded_at')->nullable()->after('completed_at');
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'order']);
        });

        Schema::create('library_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            // Reading position: PDF page or media second, shared by all the student's devices.
            $table->unsignedInteger('position')->default(0);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_items');
        Schema::dropIfExists('books');
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('reassess_reminded_at');
        });
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['quiz_id', 'mode']);
            $table->dropColumn(['mode', 'correct_count', 'total_questions', 'module_scores']);
        });
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('module_id');
            $table->dropColumn('explanation');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex(['scope']);
            $table->dropConstrainedForeignId('module_id');
            $table->dropConstrainedForeignId('course_id');
            $table->dropColumn('scope');
        });
    }
};
