<?php

namespace Tests\Feature;

use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Storage;

class OfflineModeTest extends LmsTestCase
{
    public function test_pwa_files_are_served(): void
    {
        $this->get('/manifest.webmanifest')->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.2.purpose', 'maskable');

        $sw = $this->get('/sw.js')->assertOk()->assertHeader('Service-Worker-Allowed', '/');
        $this->assertStringContainsString('application/javascript', $sw->headers->get('Content-Type'));
        $this->assertStringContainsString("const OFFLINE_URL = '/offline'", $sw->getContent());
        $this->assertStringContainsString('206', $sw->getContent());

        $this->get('/pwa/icon-512.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/pwa/../../.env')->assertNotFound();

        // The offline shell is public and contains no personal data.
        $student = $this->makeUser('student', ['name' => 'Nom Très Personnel']);
        $this->actingAs($student)->get('/offline')->assertOk()->assertDontSee('Nom Très Personnel');
    }

    public function test_package_contains_course_but_never_the_correct_answers(): void
    {
        Storage::fake('local');
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['video' => 1, 'quiz' => 1]);
        $enrollment = $this->enroll($student, $course);
        [$video, $quizLesson] = $this->lessonsOf($course)->all();
        $video->update(['video_source' => 'upload', 'video_path' => 'videos/a.mp4', 'video_size' => 5000, 'video_mime' => 'video/mp4']);

        $response = $this->actingAs($student)->getJson(route('offline.package', $enrollment))->assertOk()
            ->assertJsonPath('course.title', 'Cours test')
            ->assertJsonPath('total_bytes', 5000)
            ->assertJsonPath('media.0.url', route('media.lesson.video', $video));

        $this->assertStringNotContainsString('is_correct', $response->getContent());
        $this->assertStringNotContainsString('video_path', $response->getContent());
        $this->assertSame('4', $response->json('modules.0.lessons.1.quiz.questions.0.options.0.text'));

        // Someone else's enrollment is refused.
        $this->actingAs($this->makeUser())->getJson(route('offline.package', $enrollment))->assertForbidden();
    }

    public function test_youtube_lessons_are_flagged_online_only(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['video' => 1]);
        $enrollment = $this->enroll($student, $course);
        $this->lessonsOf($course)->first()->update(['video_source' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']);

        $this->actingAs($student)->getJson(route('offline.package', $enrollment))
            ->assertJsonPath('modules.0.lessons.0.video.kind', 'youtube')
            ->assertJsonPath('modules.0.lessons.0.video.offline', false)
            ->assertJsonPath('media', []);
    }

    public function test_sync_replays_offline_actions_and_grades_quizzes_on_the_server(): void
    {
        Storage::fake('public');
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1, 'quiz' => 1]);
        $enrollment = $this->enroll($student, $course);
        [$text, $quizLesson] = $this->lessonsOf($course)->all();
        $question = $quizLesson->quiz->questions()->first();
        $right = $question->options->firstWhere('is_correct', true);

        $this->actingAs($student)->getJson(route('offline.session'))->assertOk()->assertJsonPath('user_id', $student->id);

        $response = $this->actingAs($student)->postJson(route('offline.sync'), ['events' => [
            ['id' => 'e1', 'type' => 'complete', 'lesson_id' => $text->id],
            ['id' => 'e2', 'type' => 'quiz', 'quiz_id' => $quizLesson->quiz->id, 'answers' => [$question->id => [$right->id]], 'at' => now()->subHour()->toIso8601String()],
            ['id' => 'e3', 'type' => 'complete', 'lesson_id' => 999999],
        ]])->assertOk();

        $this->assertTrue($response->json('results.0.ok'));
        $this->assertTrue($response->json('results.1.data.passed'));
        $this->assertFalse($response->json('results.2.ok'));
        $this->assertSame(404, $response->json('results.2.status'));

        $this->assertSame(2, LessonProgress::where('enrollment_id', $enrollment->id)->count());
        $this->assertEquals(100, $enrollment->fresh()->progress_percent);
        $this->assertSame(1, QuizAttempt::count());
    }
}
