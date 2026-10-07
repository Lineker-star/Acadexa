<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CmsPage;
use App\Models\Setting;

/** The platform is called ACADEXXA; it was first spelled "ACADEXA". */
class BrandNameTest extends LmsTestCase
{
    public function test_pages_show_the_new_name_in_every_language(): void
    {
        foreach (config('app.supported_locales') as $locale) {
            foreach (['/', '/login', '/register', '/acadexxa-control/login', '/verify-certificate/none'] as $url) {
                $html = $this->withSession(['locale' => $locale])->get($url)->assertOk()->getContent();
                // Without the tags: the name is sometimes written in two colours (ACADE + XXA).
                $this->assertStringContainsString('ACADEXXA', strip_tags($html), "$locale $url");
                $this->assertDoesNotMatchRegularExpression('/acadexa/i', $html, "$locale $url");
            }
        }
        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('short_name', 'ACADEXXA');
    }

    public function test_migration_renames_the_texts_stored_in_the_database(): void
    {
        Setting::set('site_name', 'ACADEXA');
        Setting::set('cert_subheading', 'ACADEXA Learning Management System');
        Setting::set('contact_email', 'info@ACADEXA.com');
        Setting::set('facebook_url', 'https://facebook.com/ACADEXA');
        Setting::set('cert_description', 'Certificat ACADEXA-AB12-CD34-2026 délivré par Acadexa.');
        $admin = $this->makeUser('super_admin', ['name' => 'ACADEXA Admin', 'email' => 'admin@acadexa.com']);
        $content = '<h3>ACADEXA</h3><p>Écrivez à contact@ACADEXA.com ou visitez https://ACADEXA.site/aide. L’ACADEXA, c’est nous.</p><img src="/storage/ACADEXA.png" alt="Logo ACADEXA">';
        $about = CmsPage::create(['slug' => 'presentation', 'is_active' => true])
            ->translations()->create(['locale' => 'fr', 'title' => 'À propos d’ACADEXA', 'content' => $content]);
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $course->translations()->first()->update(['description' => 'Bienvenue sur ACADEXA.']);

        $migration = require database_path('migrations/2026_10_07_000002_rename_platform_to_acadexxa.php');
        // Running it twice gives the same result.
        $migration->up();
        $migration->up();

        $this->assertSame('ACADEXXA', Setting::get('site_name'));
        $this->assertSame('ACADEXXA Learning Management System', Setting::get('cert_subheading'));
        $this->assertSame('ACADEXXA Admin', $admin->fresh()->name);
        $this->assertSame('À propos d’ACADEXXA', $about->fresh()->title);
        $this->assertSame(
            '<h3>ACADEXXA</h3><p>Écrivez à contact@ACADEXA.com ou visitez https://ACADEXA.site/aide. L’ACADEXXA, c’est nous.</p><img src="/storage/ACADEXA.png" alt="Logo ACADEXXA">',
            $about->fresh()->content
        );
        $this->assertSame('Bienvenue sur ACADEXXA.', $course->translations()->first()->description);
        // Addresses, links, logins and certificate codes are identifiers: untouched.
        $this->assertSame('info@ACADEXA.com', Setting::get('contact_email'));
        $this->assertSame('https://facebook.com/ACADEXA', Setting::get('facebook_url'));
        $this->assertSame('Certificat ACADEXA-AB12-CD34-2026 délivré par Acadexxa.', Setting::get('cert_description'));
        $this->assertSame('admin@acadexa.com', $admin->fresh()->email);

        $migration->down();
        $this->assertSame('ACADEXA', Setting::get('site_name'));
        $this->assertSame($content, $about->fresh()->content);
    }

    public function test_former_admin_address_redirects_to_the_new_one(): void
    {
        $this->get('/acadexa-control/login')->assertStatus(301)->assertRedirect('/acadexxa-control/login');
        $this->get('/acadexa-control')->assertStatus(301)->assertRedirect('/acadexxa-control');
        $this->get('/acadexa-control/users?role=student')->assertRedirect('/acadexxa-control/users?role=student');
        $this->get('/robots.txt')->assertSee('Disallow: /acadexxa-control/');
    }

    public function test_certificates_issued_under_the_former_name_stay_valid(): void
    {
        $this->assertStringStartsWith('ACADEXXA-', Certificate::generateCode());

        $student = $this->makeUser('student', ['name' => 'Aïcha Mbarga']);
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        Certificate::create(['user_id' => $student->id, 'course_id' => $course->id, 'certificate_code' => 'ACADEXA-AB12-CD34-2026', 'issued_at' => now()]);

        $this->get(route('certificate.verify', 'ACADEXA-AB12-CD34-2026'))->assertOk()
            ->assertSee('Aïcha Mbarga')->assertSee('ACADEXA-AB12-CD34-2026')->assertSee(__('This is a valid ACADEXXA certificate.'));
    }

    public function test_installed_app_keeps_what_students_already_downloaded(): void
    {
        // The media cache and the local database keep their first name: a new one would start empty.
        $worker = $this->get('/sw.js')->assertOk()->getContent();
        $this->assertStringContainsString("const MEDIA_CACHE = 'acadexa-media-v1';", $worker);
        $this->assertStringContainsString("const MEDIA_CACHE = 'acadexa-media-v1';", file_get_contents(resource_path('js/offline/downloader.js')));
        $this->assertStringContainsString("const DB_NAME = 'acadexa-offline';", file_get_contents(resource_path('js/offline/db.js')));
        // Shells cached under either spelling are cleaned up.
        $this->assertStringContainsString("k.startsWith('acadexa-shell-')", $worker);
        $this->assertStringContainsString("const SHELL_CACHE = 'acadexxa-shell-'", $worker);
    }
}
