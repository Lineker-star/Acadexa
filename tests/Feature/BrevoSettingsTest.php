<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\VerificationCode;
use App\Support\Mailing;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/** E-mails use Brevo as soon as an API key exists: saved by the admin or given as a server variable. */
class BrevoSettingsTest extends LmsTestCase
{
    private const KEY = 'xkeysib-0123456789abcdef-AbCd';

    public function test_a_server_key_selects_brevo_whatever_mail_mailer_says(): void
    {
        $default = function (array $env): string {
            foreach ($env as $name => $value) {
                $_SERVER[$name] = $value;
            }
            try {
                return (require config_path('mail.php'))['default'];
            } finally {
                foreach (array_keys($env) as $name) {
                    unset($_SERVER[$name]);
                }
            }
        };

        $this->assertSame('brevo', $default(['BREVO_API_KEY' => self::KEY, 'MAIL_MAILER' => 'log']));
        $this->assertSame('brevo', $default(['BREVO_API_KEY' => self::KEY, 'MAIL_MAILER' => 'smtp']));
        $this->assertSame('log', $default(['BREVO_API_KEY' => '', 'MAIL_MAILER' => 'log']));
        $this->assertSame('smtp', $default(['BREVO_API_KEY' => '', 'MAIL_MAILER' => 'smtp']));
    }

    public function test_admin_saves_the_brevo_key_and_every_email_uses_it(): void
    {
        config(['mail.default' => 'log']);
        $admin = $this->makeUser('super_admin');
        $save = fn (array $data) => $this->actingAs($admin)->post(route('admin.settings.mail'), $data);

        // Not configured yet: the page says so and offers the field.
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()
            ->assertSee(__('learn.mail_not_ready'))->assertSee(__('learn.mail_key_help'))->assertSee('name="brevo_api_key"', false);

        // Only a Brevo API key is accepted — the SMTP key is a frequent mix-up.
        $save(['brevo_api_key' => 'xsmtpsib-abcdef'])->assertSessionHasErrors(['brevo_api_key' => __('learn.mail_key_is_smtp')]);
        $save(['brevo_api_key' => 'my-password'])->assertSessionHasErrors(['brevo_api_key' => __('learn.mail_key_format')]);

        // Brevo is asked whether it knows the key.
        Http::fake(['api.brevo.com/v3/account' => Http::sequence()
            ->push(['code' => 'unauthorized', 'message' => 'Key not found'], 401)
            ->push(['email' => 'owner@ztf.test', 'companyName' => 'ZTF'], 200),
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<1@brevo>'], 201)]);
        $save(['brevo_api_key' => self::KEY])
            ->assertSessionHasErrors(['brevo_api_key' => __('learn.mail_key_rejected', ['error' => 'Key not found'])]);
        $this->assertNull(Mailing::brevoKey());

        $save(['brevo_api_key' => ' ' . self::KEY . ' ', 'mail_from_address' => 'formation@ztf.test', 'mail_from_name' => 'ACADEXXA Formation'])
            ->assertSessionHasNoErrors()->assertSessionHas('success', __('learn.mail_key_valid', ['account' => 'owner@ztf.test']));

        // Stored encrypted; neither the page nor the settings shared with the views contain it.
        $this->assertSame(self::KEY, Mailing::brevoKey());
        $this->assertStringNotContainsString(self::KEY, DB::table('settings')->where('key', 'brevo_api_key')->value('value'));
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()
            ->assertSee('Brevo (API)')->assertSee(__('learn.mail_ready'))
            ->assertSee(__('learn.mail_key_saved', ['hint' => '…AbCd']))->assertSee('formation@ztf.test')
            ->assertDontSee(self::KEY)->assertDontSee(__('learn.mail_not_ready_help'));
        $this->assertArrayNotHasKey('brevo_api_key', Cache::get('site_settings'));

        // E-mails now leave through Brevo with that key and that sender, although MAIL_MAILER says "log".
        Mail::raw('Bonjour', fn ($m) => $m->to('awa@gmail.test')->subject('Essai'));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', self::KEY)
            && $request['sender'] === ['email' => 'formation@ztf.test', 'name' => 'ACADEXXA Formation']);

        // An empty field keeps the key; the checkbox removes it.
        $save(['mail_from_address' => 'formation@ztf.test'])->assertSessionHas('success', __('learn.mail_saved'));
        $this->assertSame(self::KEY, Mailing::brevoKey());
        $save(['remove_brevo_key' => '1'])->assertSessionHasNoErrors();
        $this->assertNull(Mailing::brevoKey());
        $this->assertFalse(Mailing::configured());
    }

    public function test_key_is_saved_when_brevo_cannot_be_reached(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $this->actingAs($this->makeUser('super_admin'))->post(route('admin.settings.mail'), ['brevo_api_key' => self::KEY])
            ->assertSessionHas('success', __('learn.mail_key_unchecked'));
        $this->assertSame(self::KEY, Mailing::brevoKey());
        $this->assertSame('admin', Mailing::keySource());
    }

    public function test_only_admins_change_the_email_settings(): void
    {
        $this->actingAs($this->makeUser('instructor'))->post(route('admin.settings.mail'), ['brevo_api_key' => self::KEY]);
        $this->actingAs($this->makeUser())->post(route('admin.settings.retry-mail'));

        $this->assertNull(Mailing::brevoKey());
    }

    public function test_registration_asks_for_a_code_only_when_emails_can_be_sent(): void
    {
        Notification::fake();
        $form = fn (string $email) => ['name' => 'Awa Ngo', 'email' => $email, 'password' => 'Password123!', 'password_confirmation' => 'Password123!'];

        // No e-mail service yet: the code could not reach anybody, the account opens directly.
        config(['mail.default' => 'log']);
        $this->post(route('register'), $form('awa@gmail.test'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        Notification::assertNotSentTo(User::where('email', 'awa@gmail.test')->first(), VerificationCode::class);
        auth()->logout();

        // Once the Brevo key is saved, the address is confirmed with a code.
        Mailing::saveKey(self::KEY);
        $this->post(route('register'), $form('binta@gmail.test'))->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        Notification::assertSentTo(User::where('email', 'binta@gmail.test')->first(), VerificationCode::class);
    }

    public function test_admin_sees_what_is_waiting_and_resends_what_brevo_refused(): void
    {
        config(['queue.default' => 'database']);
        $admin = $this->makeUser('super_admin');
        $payload = json_encode(['uuid' => (string) Str::uuid(), 'displayName' => 'App\\Notifications\\EnrollmentConfirmed', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => [], 'attempts' => 3]);

        // A notification queued half an hour ago and never sent: the scheduler service is not running.
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null,
            'available_at' => now()->subMinutes(30)->timestamp, 'created_at' => now()->subMinutes(30)->timestamp]);
        // Another one that Brevo refused three times.
        DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'payload' => $payload,
            'exception' => "Symfony\\Component\\Mailer\\Exception\\TransportException: Brevo: Sending has been rejected because the sender you used noreply@acadexxa.com is not valid. in /app/app/Mail/BrevoTransport.php:60\nStack trace:\n#0 /app/vendor/symfony/mailer/Transport/AbstractTransport.php(69)",
            'failed_at' => now()]);

        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()
            ->assertSee(__('learn.mail_queue_pending'))
            ->assertSee(__('learn.mail_queue_stuck', ['minutes' => 30]))
            ->assertSee(__('learn.mail_queue_last_error', ['error' => 'Brevo: Sending has been rejected because the sender you used noreply@acadexxa.com is not valid.']))
            ->assertDontSee('BrevoTransport.php')
            ->assertSee(__('learn.mail_queue_retry'));

        $this->actingAs($admin)->post(route('admin.settings.retry-mail'))->assertSessionHas('success', __('learn.mail_queue_retried'));
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(2, DB::table('jobs')->count());

        // Nothing failed any more: no error, no button.
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertDontSee(__('learn.mail_queue_retry'));
    }

    public function test_a_key_that_can_no_longer_be_decrypted_counts_as_missing(): void
    {
        config(['mail.default' => 'log']);
        Setting::set('brevo_api_key', 'not-an-encrypted-value');

        $this->assertNull(Mailing::brevoKey());
        $this->assertFalse(Mailing::configured());
    }
}
