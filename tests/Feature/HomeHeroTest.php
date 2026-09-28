<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Home page photo: default licensed photo (credited), replaceable in Admin → Settings. */
class HomeHeroTest extends LmsTestCase
{
    public function test_default_photo_exists_and_is_credited(): void
    {
        $this->assertFileExists(public_path(ltrim(config('lms.hero.default_image'), '/')));

        $this->get('/')->assertOk()
            ->assertSee("url('/images/hero.jpg')", false)
            ->assertSee('Serieminou');
    }

    public function test_admin_replaces_and_resets_the_photo(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)->post(route('admin.settings.update'), [
            // A real JPEG (UploadedFile::fake()->image() needs the GD extension, absent on some setups).
            'hero_image' => new UploadedFile(public_path('images/hero.jpg'), 'campus.jpg', 'image/jpeg', null, true),
        ])->assertSessionHasNoErrors();

        $path = Setting::get('hero_image');
        Storage::disk('public')->assertExists($path);
        $this->get('/')->assertOk()->assertSee(Storage::disk('public')->url($path), false)->assertDontSee('Serieminou');

        $this->actingAs($admin)->post(route('admin.settings.update'), ['remove_hero_image' => 1])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($path);
        $this->get('/')->assertOk()->assertSee("url('/images/hero.jpg')", false);
    }
}
