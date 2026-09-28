<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sign in with Google and two-factor authentication for every account:
 *  - google_id: Google account linked to the user,
 *  - has_password: false for accounts created with Google (no password chosen yet),
 *  - two_factor_method: "app" (authenticator, TOTP secret) or "email" (code sent by e-mail).
 * Additive only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->boolean('has_password')->default(true)->after('password');
            $table->string('two_factor_method', 10)->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'has_password', 'two_factor_method']);
        });
    }
};
