<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Presentation video uploaded by the instructor (alternative to a YouTube link), max 20 MB. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('intro_video_path')->nullable()->after('intro_youtube_id');
            $table->string('intro_video_mime', 100)->nullable()->after('intro_video_path');
            $table->unsignedBigInteger('intro_video_size')->nullable()->after('intro_video_mime');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['intro_video_path', 'intro_video_mime', 'intro_video_size']);
        });
    }
};
