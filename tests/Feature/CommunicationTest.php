<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Notifications\CourseAnnouncementPosted;
use App\Notifications\LessonCommentPosted;
use App\Notifications\NewMessageReceived;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Notification;

class CommunicationTest extends LmsTestCase
{
    public function test_registration_sends_welcome(): void
    {
        Notification::fake();
        $this->post('/register', [
            'name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('two-factor.challenge'));
        $user = \App\Models\User::where('email', 'awa@example.test')->first();

        // The welcome message follows the confirmation of the e-mail address (6-digit code).
        $code = null;
        Notification::assertSentTo($user, \App\Notifications\VerificationCode::class, function ($n) use (&$code) {
            $code = $n->code;
            return true;
        });
        Notification::assertNotSentTo($user, WelcomeNotification::class);
        $this->post(route('two-factor.verify'), ['code' => $code]);
        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_questions_and_replies_notify_the_right_people(): void
    {
        Notification::fake();
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $course = $this->makeCourse($instructor, ['text' => 1]);
        $this->enroll($student, $course);
        $lesson = $this->lessonsOf($course)->first();

        $this->actingAs($student)->post(route('student.lesson.comment', $lesson), ['comment' => 'Question ?'])->assertRedirect();
        Notification::assertSentTo($instructor, LessonCommentPosted::class, fn ($n) => ! $n->isReply);

        $question = $lesson->comments()->first();
        $this->actingAs($instructor)->get(route('instructor.qa.index'))->assertOk()->assertSee('Question ?');
        $this->actingAs($instructor)->post(route('instructor.comment.reply', $question), ['reply' => 'Réponse'])->assertRedirect();
        Notification::assertSentTo($student, LessonCommentPosted::class, fn ($n) => $n->isReply);
    }

    public function test_announcements_reach_enrolled_students(): void
    {
        Notification::fake();
        $instructor = $this->makeUser('instructor');
        $enrolled = $this->makeUser();
        $outsider = $this->makeUser();
        $course = $this->makeCourse($instructor);
        $this->enroll($enrolled, $course);

        $this->actingAs($instructor)->post(route('instructor.announcements.store', $course), ['title' => 'Examen', 'body' => 'Lundi'])->assertRedirect();
        Notification::assertSentTo($enrolled, CourseAnnouncementPosted::class);
        Notification::assertNotSentTo($outsider, CourseAnnouncementPosted::class);

        $this->actingAs($enrolled)->get(route('student.course.announcements', $course))->assertOk()->assertSee('Examen');
        $this->actingAs($outsider)->get(route('student.course.announcements', $course))->assertForbidden();
    }

    public function test_private_messages_between_student_and_instructor(): void
    {
        Notification::fake();
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $stranger = $this->makeUser();
        $course = $this->makeCourse($instructor);
        $this->enroll($student, $course);

        // Not enrolled: cannot write about this course.
        $this->actingAs($stranger)->post(route('messages.store'), ['course_id' => $course->id, 'subject' => 'S', 'body' => 'B'])->assertForbidden();

        $this->actingAs($student)->post(route('messages.store'), ['course_id' => $course->id, 'subject' => 'Aide', 'body' => 'Bonjour'])->assertRedirect();
        $conversation = Conversation::firstOrFail();
        Notification::assertSentTo($instructor, NewMessageReceived::class);

        $this->actingAs($stranger)->get(route('messages.show', $conversation))->assertForbidden();
        $this->actingAs($instructor)->get(route('messages.show', $conversation))->assertOk()->assertSee('Bonjour');
        $this->actingAs($instructor)->post(route('messages.reply', $conversation), ['body' => 'Oui ?'])->assertRedirect();
        Notification::assertSentTo($student, NewMessageReceived::class);
        $this->assertSame(0, $conversation->unreadCountFor($instructor));
    }

    public function test_notification_center_opens_and_marks_read(): void
    {
        $student = $this->makeUser();
        $student->notify(new WelcomeNotification());
        $notification = $student->notifications()->first();

        $this->actingAs($student)->get(route('notifications.index'))->assertOk();
        $this->actingAs($student)->get(route('notifications.open', $notification->id))->assertRedirect(route('courses.index'));
        $this->assertNotNull($notification->fresh()->read_at);

        // Another user's notification id is not reachable.
        $this->actingAs($this->makeUser())->get(route('notifications.open', $notification->id))->assertNotFound();
    }

    public function test_instructor_follow_up_pages_render(): void
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $course = $this->makeCourse($instructor, ['text' => 1, 'quiz' => 1]);
        $enrollment = $this->enroll($student, $course);

        $this->actingAs($instructor)->get(route('instructor.dashboard'))->assertOk();
        $this->actingAs($instructor)->get(route('instructor.courses.index'))->assertOk();
        $this->actingAs($instructor)->get(route('instructor.students.index', $course))->assertOk()->assertSee($student->name);
        $this->actingAs($instructor)->get(route('instructor.students.show', [$course, $enrollment]))->assertOk();
        $this->actingAs($instructor)->get(route('instructor.students.export', $course))->assertOk();
        $this->actingAs($instructor)->get(route('instructor.announcements.index', $course))->assertOk();
        $this->actingAs($instructor)->get(route('instructor.earnings'))->assertOk();
        $this->actingAs($instructor)->get(route('messages.index'))->assertOk();
        $quizLesson = $this->lessonsOf($course)[1];
        $this->actingAs($instructor)->get(route('instructor.lessons.edit', $quizLesson))->assertOk()
            ->assertSee(route('instructor.assessments.lesson', $quizLesson), false);
        $this->actingAs($instructor)->get(route('instructor.assessments.lesson', $quizLesson))
            ->assertRedirect(route('instructor.quizzes.edit', $quizLesson->quiz));
        $this->actingAs($instructor)->get(route('instructor.quizzes.edit', $quizLesson->quiz))->assertOk()->assertSee('questionList', false);
        $this->actingAs($instructor)->get(route('instructor.students.knowledge', $course))->assertOk()->assertSee($student->name);
    }
}
