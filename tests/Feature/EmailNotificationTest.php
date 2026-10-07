<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Enrollment;
use App\Notifications\AnnouncementPublished;
use App\Notifications\EnrollmentConfirmed;
use App\Notifications\InactivityReminder;
use App\Notifications\NewCoursePublished;
use App\Notifications\NewStudentEnrolled;
use App\Notifications\PasswordChanged;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/** E-mails through Brevo's API, platform news, preferences and unsubscribe. */
class EmailNotificationTest extends LmsTestCase
{
    private function useBrevo(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'xkeysib-test', 'mail.from.address' => 'noreply@acadexxa.test', 'mail.from.name' => 'ACADEXXA']);
    }

    public function test_emails_are_sent_through_the_brevo_api(): void
    {
        $this->useBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<1@brevo>'], 201)]);

        Mail::raw('Bonjour', fn ($m) => $m->to('awa@gmail.test', 'Awa')->subject('Essai'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'xkeysib-test')
                && $request['sender'] === ['email' => 'noreply@acadexxa.test', 'name' => 'ACADEXXA']
                && $request['to'] === [['email' => 'awa@gmail.test', 'name' => 'Awa']]
                && $request['subject'] === 'Essai' && $request['textContent'] === 'Bonjour';
        });
    }

    public function test_admin_test_email_reports_success_and_brevo_errors(): void
    {
        $this->useBrevo();
        $admin = $this->makeUser('super_admin');

        // First call accepted, then Brevo refuses the key.
        Http::fake(['api.brevo.com/*' => Http::sequence()
            ->push(['messageId' => 'x'], 201)
            ->whenEmpty(Http::response(['code' => 'unauthorized', 'message' => 'Key not found'], 401))]);

        $this->actingAs($admin)->post(route('admin.settings.test-mail'), ['to' => 'boss@gmail.test'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Brevo (API)');

        $this->actingAs($admin)->post(route('admin.settings.test-mail'), ['to' => 'boss@gmail.test'])
            ->assertSessionHasErrors(['to' => __('learn.mail_test_failed', ['error' => 'Brevo: Key not found'])]);
    }

    public function test_two_factor_codes_go_through_brevo_and_failures_do_not_crash(): void
    {
        $this->useBrevo();
        $user = $this->makeUser('student', ['two_factor_method' => 'email', 'two_factor_confirmed_at' => now()]);

        Http::fake(['api.brevo.com/*' => Http::sequence()
            ->push(['messageId' => 'x'], 201)
            ->whenEmpty(Http::response(['message' => 'sender not valid'], 400))]);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123!'])->assertRedirect(route('two-factor.challenge'))->assertSessionHasNoErrors();
        Http::assertSent(fn (Request $r) => $r['to'][0]['email'] === $user->email && preg_match('/\b\d{6}\b/', $r['subject']) === 1);

        // Brevo refuses (bad key, unknown sender…): a clear message instead of an error page.
        $this->travel(2)->minutes();
        $this->post(route('two-factor.resend'))->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_announcements_notify_their_audience(): void
    {
        Notification::fake();
        $admin = $this->makeUser('super_admin');
        $student = $this->makeUser('student');
        $instructor = $this->makeUser('instructor');
        $payload = ['audience' => 'students', 'translations' => ['en' => ['title' => 'Exams', 'body' => 'Exams start on Monday.'], 'fr' => ['title' => 'Examens', 'body' => 'Les examens commencent lundi.']]];

        $this->actingAs($admin)->post(route('admin.announcements.store'), $payload + ['notify' => 1])->assertRedirect();
        Notification::assertSentTo($student, AnnouncementPublished::class);
        Notification::assertNotSentTo($instructor, AnnouncementPublished::class);

        // In the reader's language.
        app()->setLocale('fr');
        $mail = (new AnnouncementPublished(Announcement::first()->load('translations')))->toMail($student);
        $this->assertSame('Examens', $mail->subject);

        Notification::fake();
        $this->actingAs($admin)->post(route('admin.announcements.store'), $payload + ['notify' => 0])->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_preferences_and_one_click_unsubscribe(): void
    {
        $student = $this->makeUser()->fresh();
        $announcement = Announcement::create(['title' => 'News', 'body' => 'Body', 'audience' => 'all', 'created_by' => $student->id, 'is_active' => true]);
        $this->assertSame(['database', 'mail'], (new AnnouncementPublished($announcement))->via($student));

        // The e-mail carries a signed unsubscribe link and the ACADEXXA design.
        $html = (string) (new AnnouncementPublished($announcement))->toMail($student)->render();
        $this->assertStringContainsString('email/unsubscribe/' . $student->id . '/news', $html);
        $this->assertStringContainsString('#0A2A5E', $html);

        $this->get(route('email.unsubscribe', ['user' => $student->id, 'type' => 'news']))->assertForbidden(); // not signed
        $this->get(URL::signedRoute('email.unsubscribe', ['user' => $student->id, 'type' => 'news']))->assertOk()->assertSee(__('learn.unsubscribed_title'));

        $student->refresh();
        $this->assertFalse($student->email_news);
        $this->assertSame(['database'], (new AnnouncementPublished($announcement))->via($student));
        // Course activity is a separate choice; security e-mails are always sent.
        $this->assertTrue($student->email_notifications);
        $student->update(['email_notifications' => false]);
        $this->assertSame(['database', 'mail'], (new PasswordChanged())->via($student->fresh()));

        $this->actingAs($student)->post(route('student.profile.update'), ['name' => $student->name, 'email_news' => 1, 'email_notifications' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($student->fresh()->email_news);
    }

    public function test_enrollment_new_course_and_password_notifications(): void
    {
        Notification::fake();
        $admin = $this->makeUser('super_admin');
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $course = $this->makeCourse($instructor, ['text' => 1], ['status' => 'pending']);

        $this->actingAs($admin)->post(route('admin.courses.approve', $course))->assertRedirect();
        Notification::assertSentTo($student, NewCoursePublished::class);
        Notification::assertNotSentTo($instructor, NewCoursePublished::class);

        $this->actingAs($student)->post(route('student.enroll', $course))->assertRedirect();
        Notification::assertSentTo($student, EnrollmentConfirmed::class);
        Notification::assertSentTo($instructor, NewStudentEnrolled::class);

        $this->actingAs($student)->post(route('profile.password'), ['current_password' => 'Password123!', 'password' => 'NewPass123!', 'password_confirmation' => 'NewPass123!'])->assertSessionHasNoErrors();
        Notification::assertSentTo($student, PasswordChanged::class);

        // Published again later: no second "new course" e-mail.
        Notification::fake();
        $course->update(['status' => 'unpublished']);
        $this->actingAs($admin)->post(route('admin.courses.approve', $course));
        Notification::assertNotSentTo($student, NewCoursePublished::class);
    }

    public function test_inactivity_reminder(): void
    {
        Notification::fake();
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 2]);
        $enrollment = $this->enroll($student, $course);

        $this->artisan('acadexxa:inactivity-reminders')->assertSuccessful();
        Notification::assertNothingSent(); // active today

        Enrollment::withoutTimestamps(fn () => $enrollment->forceFill(['updated_at' => now()->subDays(8)])->save());
        $this->artisan('acadexxa:inactivity-reminders')->assertSuccessful();
        Notification::assertSentToTimes($student, InactivityReminder::class, 1);

        // Not again the next day.
        $this->artisan('acadexxa:inactivity-reminders')->assertSuccessful();
        Notification::assertSentToTimes($student, InactivityReminder::class, 1);
    }
}
