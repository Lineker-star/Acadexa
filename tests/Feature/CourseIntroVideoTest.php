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
        ], $extra);
    }

    public function test_wizard_page_has_three_steps_and_creates_only_on_submit(): void
    {
        $instructor = $this->makeUser('instructor');
        $this->actingAs($instructor)->get(route('instructor.courses.create'))->assertOk()
            ->assertSee('data-step="2"', false)->assertSee(__('learn.intro_video'))->assertSee(__('learn.create_course'));
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
}
