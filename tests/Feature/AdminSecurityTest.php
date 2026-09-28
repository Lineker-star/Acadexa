<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Notifications\CourseReviewed;
use App\Services\Totp;
use Illuminate\Support\Facades\Notification;

class AdminSecurityTest extends LmsTestCase
{
    public function test_banned_user_cannot_log_in_and_is_signed_out(): void
    {
        $admin = $this->makeUser('super_admin');
        $student = $this->makeUser();

        $this->actingAs($admin)->post(route('admin.users.ban', $student), ['reason' => 'Fraude'])->assertRedirect();
        $this->assertTrue($student->fresh()->isBanned());

        $this->actingAs($student->fresh())->get(route('dashboard'))->assertRedirect(route('login'));

        auth()->logout();
        $this->post('/login', ['email' => $student->email, 'password' => 'Password123!'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)->post(route('admin.users.unban', $student))->assertRedirect();
        auth()->logout();
        $this->post('/login', ['email' => $student->email, 'password' => 'Password123!']);
        $this->assertAuthenticated();
    }

    public function test_regular_admin_cannot_ban_another_admin(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('admin');
        $this->actingAs($admin)->post(route('admin.users.ban', $other))->assertForbidden();
    }

    public function test_admin_permissions_restrict_sections(): void
    {
        $admin = $this->makeUser('admin', ['admin_permissions' => ['courses']]);

        $this->actingAs($admin)->get(route('admin.courses.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertForbidden();
        // (The dashboard itself uses MySQL date functions, so it is not exercised on SQLite.)
        $this->actingAs($admin)->get(route('admin.security'))->assertOk();
    }

    public function test_maintenance_mode_and_closed_registration(): void
    {
        Setting::set('maintenance_mode', '1');
        $this->get('/')->assertStatus(503);
        $this->get('/login')->assertOk();
        $this->actingAs($this->makeUser('super_admin'))->get('/')->assertOk();
        Setting::set('maintenance_mode', '0');

        auth()->logout();
        Setting::set('allow_registration', '0');
        $this->get('/register')->assertRedirect(route('login'));
        Setting::set('allow_registration', '1');
        $this->get('/register')->assertOk();
    }

    public function test_admin_two_factor_login(): void
    {
        $totp = new Totp();
        $secret = $totp->generateSecret();
        $admin = $this->makeUser('super_admin', ['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);

        // Password alone is not enough.
        $this->post(route('admin.login.post'), ['email' => $admin->email, 'password' => 'Password123!'])
            ->assertRedirect(route('admin.2fa.challenge'));
        $this->assertGuest();

        $this->post(route('admin.2fa.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('admin.2fa.verify'), ['code' => $totp->code($secret)])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        // The student login does not bypass the second factor: it asks for the same code.
        auth()->logout();
        $this->post('/login', ['email' => $admin->email, 'password' => 'Password123!'])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_admin_can_enable_two_factor(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->get(route('admin.security'))->assertOk();
        $secret = session('2fa_setup_secret');

        $this->actingAs($admin)->post(route('admin.2fa.enable'), ['code' => (new Totp())->code($secret)])->assertSessionHasNoErrors();
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
        $this->assertNotSame($secret, $admin->fresh()->getRawOriginal('two_factor_secret')); // encrypted at rest
    }

    public function test_course_review_notifies_instructor_and_reports_export(): void
    {
        Notification::fake();
        $admin = $this->makeUser('super_admin');
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['text' => 1], ['status' => 'pending']);

        $this->actingAs($admin)->post(route('admin.courses.reject', $course), ['feedback' => 'Ajoutez des quiz'])->assertRedirect();
        Notification::assertSentTo($instructor, CourseReviewed::class, fn ($n) => ! $n->approved && $n->feedback === 'Ajoutez des quiz');

        $this->actingAs($admin)->post(route('admin.courses.approve', $course))->assertRedirect();
        $this->assertNotNull($course->fresh()->published_at);

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
        $csv = $this->actingAs($admin)->get(route('admin.reports.export', 'users'))->assertOk()->streamedContent();
        $this->assertStringContainsString($instructor->email, $csv);
    }

    public function test_cms_pages_are_sanitized(): void
    {
        $html = \App\Support\HtmlSanitizer::clean('<p>ok</p><img src=x onerror="alert(1)">');
        $this->assertStringNotContainsString('onerror', $html);
    }
}
