<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\VerificationCode;
use App\Notifications\WelcomeNotification;
use App\Services\Totp;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;

/** Registration code, two-factor authentication at login, sign in with Google, profile security. */
class AuthSecurityTest extends LmsTestCase
{
    private function lastCode(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, VerificationCode::class, function ($n) use (&$code) {
            $code = $n->code;
            return true;
        });
        return $code;
    }

    private function fakeGoogle(array $attributes, bool $verified = true): void
    {
        $googleUser = (new GoogleUser)->setRaw(['email_verified' => $verified])->map($attributes + [
            'id' => 'g-123', 'name' => 'Nadia Google', 'email' => 'nadia@gmail.test', 'avatar' => 'https://lh3.googleusercontent.test/a.png',
        ]);
        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_registration_is_confirmed_with_a_code_sent_by_email(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $user = User::where('email', 'awa@example.test')->first();
        $this->assertNull($user->email_verified_at);
        $this->get(route('two-factor.challenge'))->assertOk()->assertSee(__('learn.confirm_email_title'));

        $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('two-factor.verify'), ['code' => $this->lastCode($user)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_registration_code_can_be_turned_off_by_the_admin(): void
    {
        Setting::set('registration_code', '0');
        $this->post(route('register'), [
            'name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_with_email_two_factor(): void
    {
        Notification::fake();
        $user = $this->makeUser('student', ['two_factor_method' => 'email', 'two_factor_confirmed_at' => now()]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123!'])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        // Not signed in yet: protected pages stay closed.
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->post(route('two-factor.verify'), ['code' => $this->lastCode($user)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_authenticator_app(): void
    {
        $totp = app(Totp::class);
        $secret = $totp->generateSecret();
        $instructor = $this->makeUser('instructor', ['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_method' => 'app']);

        $this->post(route('login'), ['email' => $instructor->email, 'password' => 'Password123!'])->assertRedirect(route('two-factor.challenge'));
        $this->post(route('two-factor.verify'), ['code' => $totp->code($secret)])->assertRedirect(route('instructor.dashboard'));
        $this->assertAuthenticatedAs($instructor);
    }

    public function test_login_without_two_factor_is_direct(): void
    {
        $user = $this->makeUser();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123!'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_creates_a_student_account(): void
    {
        Notification::fake();
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $this->get(route('login'))->assertSee(__('learn.continue_with_google'));
        $this->fakeGoogle([]);

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));
        $user = User::where('email', 'nadia@gmail.test')->first();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['student', 'g-123', false], [$user->role, $user->google_id, $user->has_password]);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('https://lh3.googleusercontent.test/a.png', $user->avatarUrl());
    }

    public function test_google_links_an_existing_account_and_keeps_two_factor(): void
    {
        Notification::fake();
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $user = $this->makeUser('student', ['email' => 'nadia@gmail.test', 'two_factor_method' => 'email', 'two_factor_confirmed_at' => now()]);
        $this->fakeGoogle([]);

        $this->get(route('auth.google.callback'))->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertSame('g-123', $user->fresh()->google_id);
        $this->post(route('two-factor.verify'), ['code' => $this->lastCode($user)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_refuses_admins_and_unverified_emails(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $this->makeUser('super_admin', ['email' => 'nadia@gmail.test']);
        $this->fakeGoogle([]);
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->fakeGoogle(['email' => 'other@gmail.test'], verified: false);
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertNull(User::where('email', 'other@gmail.test')->first());
    }

    public function test_profile_security_settings(): void
    {
        Notification::fake();
        $user = $this->makeUser('student', ['google_id' => 'g-1', 'has_password' => false]);

        $this->actingAs($user)->get(route('student.profile.edit'))->assertOk()
            ->assertSee(__('learn.choose_password'))->assertSee(__('learn.google_linked'));

        // Unlinking Google without a password would lock the account.
        $this->actingAs($user)->delete(route('profile.google.unlink'))->assertSessionHasErrors('google');

        // A Google account chooses a password without "current password".
        $this->actingAs($user)->post(route('profile.password'), ['password' => 'NewPass123!', 'password_confirmation' => 'NewPass123!'])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->has_password);
        $this->actingAs($user->fresh())->delete(route('profile.google.unlink'))->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->google_id);

        // E-mail two-factor: a code proves the address works.
        $this->actingAs($user)->post(route('profile.2fa.email-code'))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('profile.2fa.email'), ['code' => $this->lastCode($user)])->assertSessionHasNoErrors();
        $this->assertSame('email', $user->fresh()->twoFactorMethod());

        // Personal information.
        $this->actingAs($user)->post(route('student.profile.update'), ['name' => 'Nouveau Nom', 'preferred_language' => 'en'])->assertSessionHasNoErrors();
        $this->assertSame('Nouveau Nom', $user->fresh()->name);
    }

    public function test_authenticator_app_is_enabled_from_the_profile(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('student.profile.edit'))->assertOk();
        $secret = session('2fa_setup_secret');

        $this->actingAs($user)->post(route('profile.2fa.app'), ['code' => '123456'])->assertSessionHasErrors('app_code');
        $this->actingAs($user)->post(route('profile.2fa.app'), ['code' => app(Totp::class)->code($secret)])->assertSessionHasNoErrors();
        $this->assertSame('app', $user->fresh()->twoFactorMethod());

        $this->actingAs($user->fresh())->post(route('profile.2fa.disable'), ['code' => app(Totp::class)->code($secret)])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->twoFactorMethod());
    }
}
