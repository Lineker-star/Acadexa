<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quiz rules (attempts, timer, shuffle) and assignment submissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_attempts')->nullable()->after('passing_score');
            $table->unsignedSmallInteger('time_limit_minutes')->nullable()->after('max_attempts');
            $table->boolean('shuffle_questions')->default(false)->after('time_limit_minutes');
            $table->boolean('show_correct_answers')->default(true)->after('shuffle_questions');
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->enum('status', ['submitted', 'graded'])->default('submitted');
            $table->decimal('score', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['max_attempts', 'time_limit_minutes', 'shuffle_questions', 'show_correct_answers']);
        });
    }
};
