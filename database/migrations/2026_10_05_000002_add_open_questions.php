<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module exercises use open questions: the student writes an answer and then reads the
 * detailed answer written by the instructor (model_answer). Lesson quizzes and the final
 * evaluation stay multiple-choice (single / multiple).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('type', 10)->default('single')->change(); // was enum(single, multiple)
            $table->longText('model_answer')->nullable()->after('explanation');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('model_answer');
        });
    }
};
