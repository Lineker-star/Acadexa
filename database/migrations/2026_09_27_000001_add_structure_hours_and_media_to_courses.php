<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course -> Module -> Lesson structure with declared teaching hours,
 * uploaded/YouTube video sources and downloadable lesson resources.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('duration_hours', 6, 1)->nullable()->after('duration_minutes');
            $table->string('language', 5)->default('fr')->after('duration_hours');
            $table->boolean('is_sequential')->default(false)->after('language');
            $table->timestamp('published_at')->nullable()->after('admin_feedback');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->decimal('duration_hours', 6, 1)->nullable()->after('title');
            $table->text('description')->nullable()->after('duration_hours');
        });

        Schema::table('module_translations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
        });

        Schema::table('lessons', function (Blueprint $table) {
            // upload | youtube | vimeo | url
            $table->string('video_source', 20)->nullable()->after('type');
            $table->string('video_path')->nullable()->after('video_url');
            $table->string('video_original_name')->nullable()->after('video_path');
            $table->unsignedBigInteger('video_size')->nullable()->after('video_original_name');
            $table->string('video_mime', 100)->nullable()->after('video_size');
            $table->string('youtube_id', 20)->nullable()->after('video_mime');
            $table->boolean('is_downloadable')->default(true)->after('is_free_preview');
            $table->unsignedSmallInteger('assignment_max_score')->default(100)->after('is_downloadable');
            $table->unsignedSmallInteger('assignment_pass_score')->default(50)->after('assignment_max_score');
        });

        Schema::create('lesson_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['lesson_id', 'order']);
        });

        Schema::create('lesson_watch_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position_seconds')->default(0);
            $table->unsignedInteger('max_watched_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_watch_states');
        Schema::dropIfExists('lesson_resources');

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn([
                'video_source', 'video_path', 'video_original_name', 'video_size', 'video_mime',
                'youtube_id', 'is_downloadable', 'assignment_max_score', 'assignment_pass_score',
            ]);
        });
        Schema::table('module_translations', function (Blueprint $table) {
            $table->dropColumn('description');
        });
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['duration_hours', 'description']);
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['duration_hours', 'language', 'is_sequential', 'published_at']);
        });
    }
};
