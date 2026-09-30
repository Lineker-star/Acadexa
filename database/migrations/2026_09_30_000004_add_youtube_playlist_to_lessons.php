<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A lesson can play one video of a YouTube playlist (index), so that a whole playlist becomes
 * a sequence of lessons played inside the platform, with "next video" at the end of each one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('youtube_playlist_id', 64)->nullable()->after('youtube_id');
            $table->unsignedSmallInteger('youtube_playlist_index')->nullable()->after('youtube_playlist_id');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['youtube_playlist_id', 'youtube_playlist_index']);
        });
    }
};
