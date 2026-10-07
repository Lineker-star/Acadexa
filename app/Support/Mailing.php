<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Outgoing e-mails. They go through Brevo's API as soon as an API key exists: the one saved in
 * Admin → Settings (encrypted in the database, so the web and the scheduler services share it) or,
 * failing that, the server variable BREVO_API_KEY. The sender is chosen in the same two places.
 */
class Mailing
{
    /** Settings that must never reach a view. */
    public const SECRET_SETTINGS = ['brevo_api_key'];

    private const ACCOUNT_ENDPOINT = 'https://api.brevo.com/v3/account';

    /** Applied when the mailer is first needed (see AppServiceProvider). */
    public static function apply(): void
    {
        if (self::brevoKey() !== null) {
            config(['mail.default' => 'brevo']);
        }
        config(['mail.from' => self::sender()]);
    }

    public static function brevoKey(): ?string
    {
        return self::savedKey() ?? (config('services.brevo.key') ?: null);
    }

    /** Where the key in use comes from: "admin" (saved in the settings), "server" (BREVO_API_KEY) or null. */
    public static function keySource(): ?string
    {
        return match (true) {
            self::savedKey() !== null            => 'admin',
            filled(config('services.brevo.key')) => 'server',
            default                              => null,
        };
    }

    public static function saveKey(?string $key): void
    {
        Setting::set('brevo_api_key', filled($key) ? Crypt::encryptString($key) : '');
    }

    /** True when e-mails really leave the server, and not only to the log file. */
    public static function configured(): bool
    {
        return self::brevoKey() !== null || ! in_array(config('mail.default'), ['log', 'brevo'], true);
    }

    /** @return array{address: string, name: string} */
    public static function sender(): array
    {
        return [
            'address' => self::setting('mail_from_address') ?? config('mail.from.address'),
            'name'    => self::setting('mail_from_name') ?? config('mail.from.name'),
        ];
    }

    /**
     * Asks Brevo whether it accepts the key.
     *
     * @return array{ok: bool|null, account?: string, error?: string} ok is null when Brevo could not be reached
     */
    public static function checkKey(string $key): array
    {
        try {
            $response = Http::withHeaders(['api-key' => $key, 'accept' => 'application/json'])
                ->timeout(10)->get(self::ACCOUNT_ENDPOINT);
        } catch (\Throwable $e) {
            return ['ok' => null];
        }

        if ($response->successful()) {
            return ['ok' => true, 'account' => (string) ($response->json('email') ?: $response->json('companyName'))];
        }
        if ($response->serverError()) {
            return ['ok' => null];
        }

        return ['ok' => false, 'error' => (string) ($response->json('message') ?: 'HTTP ' . $response->status())];
    }

    /** What the admin sees in Settings → E-mails. */
    public static function status(): array
    {
        $key = self::brevoKey();

        return [
            'configured' => self::configured(),
            'service'    => $key !== null || config('mail.default') === 'brevo' ? 'Brevo (API)' : strtoupper((string) config('mail.default')),
            'key_source' => self::keySource(),
            'key_hint'   => $key !== null ? '…' . substr($key, -4) : null,
            'sender'     => self::sender(),
            'from'       => ['address' => self::setting('mail_from_address'), 'name' => self::setting('mail_from_name')],
            'queue'      => self::queue(),
        ];
    }

    /**
     * Notifications (and their e-mails) are sent by the queue, which the scheduler service empties
     * every minute. Null when nothing is queued (QUEUE_CONNECTION=sync).
     *
     * @return array{pending: int, waiting_minutes: int, failed: int, last_error: string|null}|null
     */
    public static function queue(): ?array
    {
        if (config('queue.default') !== 'database') {
            return null;
        }

        return rescue(function () {
            $jobs = DB::table(config('queue.connections.database.table', 'jobs'));
            $failed = DB::table(config('queue.failed.table', 'failed_jobs'));
            $oldest = (clone $jobs)->min('created_at');
            $last = (clone $failed)->orderByDesc('id')->value('exception');

            return [
                'pending'         => (clone $jobs)->count(),
                'waiting_minutes' => $oldest ? max(0, intdiv(now()->timestamp - (int) $oldest, 60)) : 0,
                'failed'          => (clone $failed)->count(),
                'last_error'      => $last ? self::reason($last) : null,
            ];
        }, null, false);
    }

    /** First line of a stored exception without its class and file: the provider's own explanation. */
    private static function reason(string $exception): string
    {
        $line = trim(Str::before($exception, "\n"));
        $line = preg_replace('/^[A-Za-z0-9_\\\\]+:\s+/', '', $line);
        $line = preg_replace('/\s+in\s+(?:(?!\sin\s).)+:\d+$/', '', $line);

        return Str::limit($line, 220);
    }

    /** The key saved by the admin, decrypted; null when there is none or it can no longer be read (APP_KEY changed). */
    private static function savedKey(): ?string
    {
        $value = self::setting('brevo_api_key');
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** A setting, or null when it is empty or the database is not ready yet (first installation). */
    private static function setting(string $key): ?string
    {
        $value = rescue(fn () => Setting::get($key), null, false);

        return filled($value) ? (string) $value : null;
    }
}
