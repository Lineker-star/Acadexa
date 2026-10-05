<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-mail notifications: a separate preference for the platform's news (announcements, new
 * courses, reminders) and the date of the last "come back to your course" reminder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_news')->default(true)->after('email_notifications');
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('inactivity_reminded_at')->nullable()->after('reassess_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('inactivity_reminded_at');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_news');
        });
    }
};
