<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Notifications\CourseSubmittedForReview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class CourseBuilderTest extends LmsTestCase
{
    public function test_instructor_builds_a_course_with_hours_modules_and_lessons(): void
    {
        $instructor = $this->makeUser('instructor');
        $category = $this->makeCategory();

        $this->actingAs($instructor)->get(route('instructor.courses.create'))->assertOk();

        $this->actingAs($instructor)->post(route('instructor.courses.store'), [
            'language' => 'fr', 'title' => 'Comptabilité générale', 'description' => 'Les bases',
            'category_id' => $category->id, 'level' => 'beginner', 'duration_hours' => 40,
        ])->assertRedirect();

        $course = Course::firstOrFail();
        $this->assertSame('40.0', $course->duration_hours);
        $this->assertSame('draft', $course->status);

        // Module with its own hours, in French and English.
        $this->actingAs($instructor)->post(route('instructor.modules.store', $course), [
            'duration_hours' => 12,
            'translations' => ['fr' => ['title' => 'Le bilan', 'description' => 'Objectifs'], 'en' => ['title' => 'The balance sheet']],
        ])->assertRedirect();
        $module = Module::firstOrFail();
        $this->assertSame('12.0', $module->duration_hours);
        $this->assertSame('The balance sheet', $module->title('en'));
        $this->assertSame('Le bilan', $module->title('fr'));

        // Lessons are created then edited on their own page.
        $this->actingAs($instructor)->post(route('instructor.lessons.store', $module), ['title' => 'Introduction', 'type' => 'video'])
            ->assertRedirect();
        $lesson = Lesson::firstOrFail();
        $this->actingAs($instructor)->get(route('instructor.lessons.edit', $lesson))->assertOk()->assertSee('videoUploader', false);

        // A YouTube link is stored as an id and played inside the platform.
        $this->actingAs($instructor)->put(route('instructor.lessons.update', $lesson), [
            'type' => 'video', 'duration_minutes' => 12, 'video_source' => 'youtube',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?t=10',
            'translations' => ['fr' => ['title' => 'Introduction', 'content' => '<p>Bonjour<script>alert(1)</script></p>']],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $lesson->refresh();
        $this->assertSame('youtube', $lesson->videoKind());
        $this->assertSame('dQw4w9WgXcQ', $lesson->youtube_id);
        $this->assertStringNotContainsString('script', $lesson->body('fr'));
        $this->assertSame(12, $course->fresh()->duration_minutes);

        $this->actingAs($instructor)->get(route('instructor.courses.edit', ['course' => $course, 'tab' => 'curriculum']))
            ->assertOk()->assertSee('Le bilan')->assertSee('Introduction');
    }

    public function test_invalid_youtube_link_is_rejected(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['video' => 1], ['status' => 'draft']);
        $lesson = $this->lessonsOf($course)->first();

        $this->actingAs($instructor)->put(route('instructor.lessons.update', $lesson), [
            'type' => 'video', 'duration_minutes' => 5, 'video_source' => 'youtube', 'youtube_url' => 'https://example.com/video',
            'translations' => ['fr' => ['title' => 'X']],
        ])->assertSessionHasErrors('youtube_url');
    }

    public function test_chunked_video_upload_assembles_and_resumes(): void
    {
        Storage::fake('local');
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['video' => 1], ['status' => 'draft']);
        $lesson = $this->lessonsOf($course)->first();
        $lesson->update(['duration_minutes' => 0]);

        // Minimal MP4 header ("ftyp" box) so finfo recognises a video, split in 3 chunks.
        $bytes = "\x00\x00\x00\x20ftypisom\x00\x00\x02\x00isomiso2avc1mp41" . str_repeat("\x00", 3000);
        $parts = str_split($bytes, 1100);
        $uploadId = 'abcdef1234567890';

        foreach ([0, 2] as $index) { // chunk 1 "lost" on the first pass
            $this->actingAs($instructor)->post(route('instructor.lessons.video.chunk', $lesson), [
                'upload_id' => $uploadId, 'index' => $index, 'total' => 3, 'filename' => 'cours.mp4',
                'size' => strlen($bytes), 'duration_seconds' => 125,
                'chunk' => UploadedFile::fake()->createWithContent('chunk', $parts[$index]),
            ], ['Accept' => 'application/json'])->assertOk()->assertJson(['done' => false]);
        }

        $this->actingAs($instructor)->getJson(route('instructor.lessons.video.status', $lesson) . '?upload_id=' . $uploadId)
            ->assertJson(['received' => [0, 2]]);

        $this->actingAs($instructor)->post(route('instructor.lessons.video.chunk', $lesson), [
            'upload_id' => $uploadId, 'index' => 1, 'total' => 3, 'filename' => 'cours.mp4',
            'size' => strlen($bytes), 'duration_seconds' => 125,
            'chunk' => UploadedFile::fake()->createWithContent('chunk', $parts[1]),
        ], ['Accept' => 'application/json'])->assertOk()->assertJson(['done' => true]);

        $lesson->refresh();
        $this->assertSame('upload', $lesson->video_source);
        Storage::disk('local')->assertExists($lesson->video_path);
        $this->assertSame($bytes, Storage::disk('local')->get($lesson->video_path));
        $this->assertSame(3, $lesson->duration_minutes); // 125 s rounded up
    }

    public function test_upload_rejects_files_that_are_not_videos(): void
    {
        Storage::fake('local');
        $instructor = $this->makeUser('instructor');
        $lesson = $this->lessonsOf($this->makeCourse($instructor, ['video' => 1], ['status' => 'draft']))->first();

        $this->actingAs($instructor)->post(route('instructor.lessons.video.chunk', $lesson), [
            'upload_id' => 'zzzzzzzzzzzz', 'index' => 0, 'total' => 1, 'filename' => 'virus.mp4', 'size' => 20,
            'chunk' => UploadedFile::fake()->createWithContent('chunk', '<?php echo "hi"; ?>'),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertNull($lesson->fresh()->video_path);
    }

    public function test_quiz_builder_creates_questions_and_validates_answers(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['quiz' => 1], ['status' => 'draft']);
        $quiz = $this->lessonsOf($course)->first()->quiz;

        $this->actingAs($instructor)->post(route('instructor.questions.store', $quiz), [
            'question' => 'Capitale du Cameroun ?', 'type' => 'single',
            'options' => [['text' => 'Douala'], ['text' => 'Yaoundé'], ['text' => '']], 'correct' => [1],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $question = \App\Models\QuizQuestion::latest('id')->first();
        $this->assertSame(['Douala', 'Yaoundé'], $question->options->pluck('option_text')->all());
        $this->assertTrue($question->options[1]->is_correct);

        // Single-answer question with two correct answers is refused.
        $this->actingAs($instructor)->post(route('instructor.questions.store', $quiz), [
            'question' => 'X', 'type' => 'single', 'options' => [['text' => 'a'], ['text' => 'b']], 'correct' => [0, 1],
        ])->assertSessionHasErrors('correct');
    }

    public function test_reorder_moves_lessons_between_modules(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['text' => 2], ['status' => 'draft']);
        $first = $course->modules()->first();
        $second = Module::create(['course_id' => $course->id, 'order' => 2, 'title' => 'M2', 'duration_hours' => 2]);
        [$a, $b] = $this->lessonsOf($course)->all();

        $this->actingAs($instructor)->postJson(route('instructor.lessons.reorder', $course), [
            'modules' => [['id' => $first->id, 'lessons' => [$b->id]], ['id' => $second->id, 'lessons' => [$a->id]]],
        ])->assertOk();

        $this->assertSame($second->id, $a->fresh()->module_id);
        $this->assertSame(1, $b->fresh()->order);
    }

    public function test_other_instructors_cannot_edit_a_course(): void
    {
        $owner = $this->makeUser('instructor');
        $other = $this->makeUser('instructor');
        $course = $this->makeCourse($owner, ['text' => 1], ['status' => 'draft']);

        $this->actingAs($other)->get(route('instructor.courses.edit', $course))->assertForbidden();
        $this->actingAs($other)->get(route('instructor.lessons.edit', $this->lessonsOf($course)->first()))->assertForbidden();
    }

    public function test_submission_requires_complete_checklist_and_notifies_admins(): void
    {
        Notification::fake();
        $admin = $this->makeUser('super_admin');
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['video' => 1], ['status' => 'draft', 'thumbnail' => 'x.jpg']);

        // Video lesson without a video: refused.
        $this->actingAs($instructor)->post(route('instructor.courses.submit', $course))->assertSessionHasErrors('submit');
        $this->assertSame('draft', $course->fresh()->status);

        $this->lessonsOf($course)->first()->update(['video_source' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']);
        // Still refused: no lesson quiz, module exercise or final evaluation yet.
        $this->actingAs($instructor)->post(route('instructor.courses.submit', $course))->assertSessionHasErrors('submit');

        $this->addAssessmentPath($course);
        $this->actingAs($instructor)->post(route('instructor.courses.submit', $course))->assertSessionHasNoErrors();
        $this->assertSame('pending', $course->fresh()->status);
        Notification::assertSentTo($admin, CourseSubmittedForReview::class);

        // Locked while under review.
        $this->actingAs($instructor)->post(route('instructor.modules.store', $course), [
            'duration_hours' => 1, 'translations' => ['fr' => ['title' => 'Nouveau']],
        ])->assertStatus(423);
    }
}
