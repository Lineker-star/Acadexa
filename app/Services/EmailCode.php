<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\VerificationCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Six-digit codes sent by e-mail: confirming the address at registration and, for accounts that
 * chose it, the second step of the login. Only a hash is kept (cache, 10 minutes, 5 tries).
 */
class EmailCode
{
    public const TTL_MINUTES = 10;
    private const MAX_TRIES = 5;
    public const RESEND_SECONDS = 60;

    /** @return bool false when the e-mail could not be sent (the provider's error is logged) */
    public function send(User $user, string $purpose): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->key($user, $purpose), ['hash' => Hash::make($code), 'tries' => 0], now()->addMinutes(self::TTL_MINUTES));
        Cache::put($this->key($user, $purpose) . ':sent', now()->timestamp, now()->addMinutes(self::TTL_MINUTES));

        // Sent right away (not queued): the person is waiting for it on the screen.
        try {
            $user->notifyNow(new VerificationCode($code, $purpose));
        } catch (\Throwable $e) {
            report($e);
            Cache::forget($this->key($user, $purpose) . ':sent'); // allow an immediate retry
            return false;
        }
        return true;
    }

    /** Seconds before another code can be requested (0 = now). */
    public function waitBeforeResend(User $user, string $purpose): int
    {
        $sent = Cache::get($this->key($user, $purpose) . ':sent');
        return $sent ? max(0, self::RESEND_SECONDS - (now()->timestamp - $sent)) : 0;
    }

    public function verify(User $user, string $purpose, string $code): bool
    {
        $key = $this->key($user, $purpose);
        $entry = Cache::get($key);
        $code = preg_replace('/\D/', '', $code);

        if (! $entry || $entry['tries'] >= self::MAX_TRIES) {
            return false;
        }
        if (strlen($code) === 6 && Hash::check($code, $entry['hash'])) {
            Cache::forget($key);
            return true;
        }
        $entry['tries']++;
        Cache::put($key, $entry, now()->addMinutes(self::TTL_MINUTES));
        return false;
    }

    private function key(User $user, string $purpose): string
    {
        return "email_code:{$purpose}:{$user->id}";
    }
}
