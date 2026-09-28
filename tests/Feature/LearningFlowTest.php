<?php

namespace Tests\Feature;

use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\Lesson;
use App\Notifications\AssignmentGraded;
use App\Notifications\AssignmentSubmitted;
use App\Notifications\CertificateIssued;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class LearningFlowTest extends LmsTestCase
{
    public function test_player_opens_the_requested_lesson(): void
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $course = $this->makeCourse($instructor, ['text' => 3]);
        $enrollment = $this->enroll($student, $course);
        $third = $this->lessonsOf($course)[2];

        $this->actingAs($student)->get(route('student.courses.player', $enrollment) . '?lesson=' . $third->id)
            ->assertOk()->assertSee($third->title(), false)->assertSee('"lessonId":' . $third->id, false);
        $this->assertSame($third->id, $enrollment->fresh()->last_lesson_id);
    }

    public function test_my_courses_links_to_the_player(): void
    {
        $student = $this->makeUser();
        $enrollment = $this->enroll($student, $this->makeCourse($this->makeUser('instructor')));

        $this->actingAs($student)->get(route('student.courses.index'))
            ->assertOk()->assertSee(route('student.courses.player', $enrollment), false)
            ->assertSee('data-offline-download="' . $enrollment->id . '"', false);
    }

    public function test_sequential_course_locks_later_lessons(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 2], ['is_sequential' => true]);
        $enrollment = $this->enroll($student, $course);
        [$first, $second] = $this->lessonsOf($course)->all();

        $this->actingAs($student)->postJson(route('student.lesson.complete', $second))->assertStatus(423);
        $this->actingAs($student)->get(route('student.courses.player', $enrollment) . '?lesson=' . $second->id)
            ->assertOk()->assertSee('"lessonId":' . $first->id, false);

        $this->actingAs($student)->postJson(route('student.lesson.complete', $first))->assertOk();
        $this->actingAs($student)->postJson(route('student.lesson.complete', $second))->assertOk()->assertJson(['completed' => true]);
    }

    public function test_video_must_be_watched_and_skipping_does_not_count(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['video' => 1, 'text' => 1]);
        $this->enroll($student, $course);
        $video = $this->lessonsOf($course)->first();
        $video->update(['video_source' => Lesson::VIDEO_YOUTUBE, 'youtube_id' => 'dQw4w9WgXcQ']);

        // 30 s played of a 100 s video: not enough.
        $this->actingAs($student)->postJson(route('student.lesson.watch', $video), ['position' => 95, 'duration' => 100, 'watched_delta' => 30])
            ->assertJson(['watched_percent' => 30, 'can_complete' => false]);
        $this->actingAs($student)->postJson(route('student.lesson.complete', $video))->assertStatus(422);

        // A single report cannot claim more than two minutes of playback.
        $this->actingAs($student)->postJson(route('student.lesson.watch', $video), ['position' => 100, 'duration' => 100, 'watched_delta' => 9999])
            ->assertJson(['watched_percent' => 100, 'can_complete' => true]);
        $this->actingAs($student)->postJson(route('student.lesson.complete', $video))->assertOk()->assertJson(['progress' => 50]);
    }

    public function test_quiz_pass_completes_lesson_and_attempt_limit_applies(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['quiz' => 1, 'text' => 1]);
        $this->enroll($student, $course);
        $lesson = $this->lessonsOf($course)->first();
        $quiz = $lesson->quiz;
        $quiz->update(['max_attempts' => 2]);
        $question = $quiz->questions()->first();
        $right = $question->options->firstWhere('is_correct', true);
        $wrong = $question->options->firstWhere('is_correct', false);

        // Quizzes cannot be completed with the button.
        $this->actingAs($student)->postJson(route('student.lesson.complete', $lesson))->assertStatus(422);

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => [$question->id => [$wrong->id]]])
            ->assertOk()->assertJson(['passed' => false, 'score' => 0, 'attempts_left' => 1])
            ->assertJsonPath("review.{$question->id}.answer", [$right->id]);

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => [$question->id => [$right->id]]])
            ->assertOk()->assertJson(['passed' => true, 'score' => 100, 'progress' => ['progress' => 50]]);

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => [$question->id => [$right->id]]])
            ->assertStatus(422);
    }

    public function test_timed_quiz_requires_start_and_enforces_time(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['quiz' => 1]);
        $this->enroll($student, $course);
        $quiz = $this->lessonsOf($course)->first()->quiz;
        $quiz->update(['time_limit_minutes' => 1]);
        $question = $quiz->questions()->first();
        $right = $question->options->firstWhere('is_correct', true);

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => [$question->id => [$right->id]]])
            ->assertStatus(422);

        $this->actingAs($student)->postJson(route('student.quiz.start', $quiz))->assertOk()->assertJson(['remaining_seconds' => 60]);
        $this->travel(5)->minutes();
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => [$question->id => [$right->id]]])
            ->assertOk()->assertJson(['passed' => false]);
    }

    public function test_certificate_is_issued_once_at_one_hundred_percent(): void
    {
        Notification::fake();
        Storage::fake('public');
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $this->enroll($student, $course);

        $this->actingAs($student)->postJson(route('student.lesson.complete', $this->lessonsOf($course)->first()))
            ->assertOk()->assertJson(['completed' => true, 'certificate_issued' => true]);

        $this->assertSame(1, Certificate::count());
        Notification::assertSentToTimes($student, CertificateIssued::class, 1);
    }

    public function test_assignment_submission_and_grading(): void
    {
        Notification::fake();
        Storage::fake('local');
        Storage::fake('public');
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $course = $this->makeCourse($instructor, ['assignment' => 1, 'text' => 1]);
        $enrollment = $this->enroll($student, $course);
        $lesson = $this->lessonsOf($course)->first();

        $this->actingAs($student)->post(route('student.assignment.submit', $lesson), [])->assertSessionHasErrors('content');

        $this->actingAs($student)->post(route('student.assignment.submit', $lesson), [
            'content' => 'Mon travail', 'file' => UploadedFile::fake()->create('devoir.pdf', 50, 'application/pdf'),
        ])->assertRedirect();
        $submission = AssignmentSubmission::firstOrFail();
        Notification::assertSentTo($instructor, AssignmentSubmitted::class);

        // Only the author and the course's instructor can download the file.
        $this->actingAs($this->makeUser())->get(route('media.submission', $submission))->assertForbidden();
        $this->actingAs($instructor)->get(route('media.submission', $submission))->assertOk();

        $this->actingAs($instructor)->get(route('instructor.submissions.index'))->assertOk()->assertSee($student->name);
        $this->actingAs($instructor)->post(route('instructor.submissions.grade', $submission), ['score' => 30, 'feedback' => 'À revoir'])->assertRedirect();
        $this->assertEquals(0, $enrollment->fresh()->progress_percent);

        $this->actingAs($instructor)->post(route('instructor.submissions.grade', $submission), ['score' => 80])->assertRedirect();
        $this->assertEquals(50, $enrollment->fresh()->progress_percent);
        Notification::assertSentTo($student, AssignmentGraded::class);

        // A passed assignment cannot be resubmitted.
        $this->actingAs($student)->post(route('student.assignment.submit', $lesson), ['content' => 'v2'])->assertSessionHasErrors('content');
    }

    public function test_video_files_are_protected_and_support_range_requests(): void
    {
        Storage::fake('local');
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $course = $this->makeCourse($instructor, ['video' => 1]);
        $lesson = $this->lessonsOf($course)->first();
        Storage::disk('local')->put('videos/test.mp4', str_repeat('A', 1000));
        $lesson->update(['video_source' => 'upload', 'video_path' => 'videos/test.mp4', 'video_mime' => 'video/mp4', 'video_size' => 1000]);

        $this->get(route('media.lesson.video', $lesson))->assertForbidden();
        $this->actingAs($student)->get(route('media.lesson.video', $lesson))->assertForbidden();

        $this->enroll($student, $course);
        $this->actingAs($student)->get(route('media.lesson.video', $lesson), ['Range' => 'bytes=100-199'])
            ->assertStatus(206)->assertHeader('Content-Range', 'bytes 100-199/1000');

        // Expired trial: no more access.
        $student->update(['trial_started_at' => now()->subDays(60)]);
        $this->actingAs($student)->get(route('media.lesson.video', $lesson))->assertForbidden();

        // Free preview: open to guests.
        $lesson->update(['is_free_preview' => true]);
        auth()->logout();
        $this->get(route('media.lesson.video', $lesson))->assertOk();
        $this->get(route('courses.preview', [$course->slug, $lesson]))->assertOk();
    }

    public function test_reviews_require_completion(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $enrollment = $this->enroll($student, $course);

        $this->actingAs($student)->post(route('student.review.store', $course), ['rating' => 5])->assertForbidden();
        $enrollment->update(['progress_percent' => 100]);
        $this->actingAs($student)->post(route('student.review.store', $course), ['rating' => 5])->assertRedirect();
    }
}
