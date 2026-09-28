<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bans (distinct from deactivation), TOTP two-factor authentication
 * and the per-user e-mail notification preference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('is_active');
            $table->string('ban_reason', 500)->nullable()->after('banned_at');
            $table->text('two_factor_secret')->nullable()->after('admin_permissions');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            $table->boolean('email_notifications')->default(true)->after('two_factor_confirmed_at');
        });

        // Accounts created while verification was disabled count as verified, so turning the
        // "require_email_verification" setting on later does not lock existing users out.
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned_at', 'ban_reason', 'two_factor_secret', 'two_factor_confirmed_at', 'email_notifications']);
        });
    }
};
