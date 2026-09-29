<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Home page image: default ACADEXA illustration, replaceable in Admin → Settings. */
class HomeHeroTest extends LmsTestCase
{
    public function test_default_illustration_exists(): void
    {
        $this->assertFileExists(public_path(ltrim(config('lms.hero.default_image'), '/')));
        $this->get('/')->assertOk()->assertSee("url('/images/hero.svg')", false);
    }

    public function test_courses_without_picture_get_a_generated_cover(): void
    {
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $this->assertStringContainsString('/course-covers/' . $course->id . '.svg', $course->thumbnailUrl());

        $response = $this->get($course->thumbnailUrl())->assertOk();
        $this->assertStringStartsWith('image/svg+xml', $response->headers->get('Content-Type'));
        $response->assertSee('Cours test', false)->assertSee('<svg', false);
    }

    public function test_admin_replaces_and_resets_the_photo(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)->post(route('admin.settings.update'), [
            // A real PNG (UploadedFile::fake()->image() needs the GD extension, absent on some setups).
            'hero_image' => new UploadedFile(public_path('favicon.png'), 'campus.png', 'image/png', null, true),
        ])->assertSessionHasNoErrors();

        $path = Setting::get('hero_image');
        Storage::disk('public')->assertExists($path);
        $this->get('/')->assertOk()->assertSee(Storage::disk('public')->url($path), false);

        $this->actingAs($admin)->post(route('admin.settings.update'), ['remove_hero_image' => 1])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($path);
        $this->get('/')->assertOk()->assertSee("url('/images/hero.svg')", false);
    }

    public function test_footer_only_on_the_home_page(): void
    {
        $this->get('/')->assertOk()->assertSee('acadexxa-footer', false);
        $this->get(route('courses.index'))->assertOk()->assertDontSee('acadexxa-footer', false);
        $this->get(route('contact'))->assertOk()->assertDontSee('acadexxa-footer', false);
    }
}
