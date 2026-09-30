<?php

namespace Tests\Feature;

use App\Models\Course;

/** New course wizard: the presentation video (YouTube link) plays on the course page, inside the platform. */
class CourseIntroVideoTest extends LmsTestCase
{
    private function payload(array $extra = []): array
    {
        return array_merge([
            'language' => 'fr', 'title' => 'Excel pour débutants', 'description' => 'Apprendre Excel pas à pas.',
            'category_id' => $this->makeCategory()->id, 'level' => 'beginner', 'duration_hours' => 12,
            'modules' => [['title' => 'Module 1', 'lessons' => 2]],
            'content_youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
        ], $extra);
    }

    public function test_wizard_page_has_three_steps_and_creates_only_on_submit(): void
    {
        $instructor = $this->makeUser('instructor');
        $this->actingAs($instructor)->get(route('instructor.courses.create'))->assertOk()
            ->assertSee('data-step="3"', false)->assertSee(__('learn.intro_video'))->assertSee(__('learn.create_course'));
        $this->assertSame(0, Course::count());
    }

    public function test_course_created_with_a_youtube_presentation_video(): void
    {
        $instructor = $this->makeUser('instructor');

        $this->actingAs($instructor)->post(route('instructor.courses.store'), $this->payload(['intro_youtube_url' => 'bad link']))
            ->assertSessionHasErrors('intro_youtube_url');
        $this->assertSame(0, Course::count());

        $this->actingAs($instructor)->post(route('instructor.courses.store'), $this->payload(['intro_youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?si=abc']))
            ->assertSessionHasNoErrors();
        $course = Course::first();
        $this->assertSame('dQw4w9WgXcQ', $course->intro_youtube_id);

        $course->update(['status' => 'published']);
        $this->get(route('courses.show', $course->slug))->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

        // Without a link: no video block.
        $this->actingAs($instructor)->post(route('instructor.courses.store'), $this->payload(['title' => 'Sans vidéo']))->assertSessionHasNoErrors();
        $this->assertNull(Course::latest('id')->first()->intro_youtube_id);
    }

    public function test_presentation_video_uploaded_in_chunks_and_attached_on_creation(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $instructor = $this->makeUser('instructor');
        // Minimal MP4 header (ftyp box): recognised as video/mp4.
        $mp4 = "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom" . str_repeat("\x00", 2000);
        $chunk = fn () => \Illuminate\Http\UploadedFile::fake()->createWithContent('chunk', $mp4);

        $token = $this->actingAs($instructor)->post(route('instructor.intro-video.chunk'), [
            'upload_id' => 'abcdef123456', 'index' => 0, 'total' => 1, 'filename' => 'intro.mp4', 'size' => strlen($mp4), 'chunk' => $chunk(),
        ])->assertOk()->assertJson(['done' => true])->json('token');

        // Over 20 MB: refused.
        $this->actingAs($instructor)->postJson(route('instructor.intro-video.chunk'), [
            'upload_id' => 'abcdef999999', 'index' => 0, 'total' => 1, 'filename' => 'big.mp4', 'size' => 21 * 1024 * 1024, 'chunk' => $chunk(),
        ])->assertStatus(422);

        $this->actingAs($instructor)->post(route('instructor.courses.store'), $this->payload(['intro_source' => 'upload', 'intro_video_token' => $token]))
            ->assertSessionHasNoErrors();
        $course = Course::first();
        $this->assertNotNull($course->intro_video_path);
        $this->assertNull($course->intro_youtube_id);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($course->intro_video_path);

        // Not published: hidden from visitors, visible to its instructor (course page preview).
        $this->actingAs($instructor)->get(route('media.course.intro', $course))->assertOk();
        $this->actingAs($instructor)->get(route('courses.show', $course->slug))->assertOk()->assertSee(route('media.course.intro', $course), false);
        auth()->logout();
        $this->get(route('media.course.intro', $course))->assertNotFound();
    }

    public function test_admin_publishes_a_draft_directly(): void
    {
        $admin = $this->makeUser('super_admin');
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1], ['status' => 'draft']);

        $this->get(route('courses.show', $course->slug))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.courses.show', $course))->assertOk()
            ->assertSee(__('learn.publish_now'))->assertSee(__('learn.publish_missing'));
        $this->actingAs($admin)->post(route('admin.courses.approve', $course))->assertRedirect();
        $this->assertSame('published', $course->fresh()->status);

        auth()->logout();
        $this->get(route('courses.show', $course->slug))->assertOk();
    }
}
