<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;

/** Every page must render for every role and in every language: catches broken views and missing strings. */
class PageSweepTest extends LmsTestCase
{
    public function test_every_page_renders_in_every_language(): void
    {
        $this->seed(); // demo users, courses, categories and pages
        $admin = \App\Models\User::where('role', 'super_admin')->first();
        $instructor = \App\Models\User::where('role', 'instructor')->first();
        $student = \App\Models\User::where('role', 'student')->first();
        $course = \App\Models\Course::where('status', 'published')->first();
        $this->enroll($student, $course);
        $lesson = $course->lessons()->first();
        $enrollment = \App\Models\Enrollment::where('user_id', $student->id)->first();

        $failures = [];
        foreach (config('app.supported_locales') as $locale) {
            foreach ([null => null, 'student' => $student, 'instructor' => $instructor, 'admin' => $admin] as $label => $user) {
                foreach (Route::getRoutes() as $route) {
                    if (! in_array('GET', $route->methods()) || $route->parameterNames() || str_contains($route->uri(), '{')) continue;
                    if (in_array($route->uri(), ['up', 'acadexa-control/two-factor', 'offline/session', 'sw.js', 'icons.svg', 'manifest.webmanifest'])) continue;
                    // The admin dashboard uses MySQL date functions (MONTH/YEAR).
                    if ($route->uri() === 'acadexa-control' && \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') continue;
                    $this->app['auth']->forgetGuards();
                    $request = $this->withSession(['locale' => $locale]);
                    $response = $user ? $request->actingAs($user)->get('/' . ltrim($route->uri(), '/')) : $request->get('/' . ltrim($route->uri(), '/'));
                    if ($response->baseResponse->getStatusCode() >= 500) {
                        $failures[] = "[$locale] " . ($label ?: 'guest') . ' ' . $route->uri() . ' => ' . substr((string) $response->exception?->getMessage(), 0, 200);
                    }
                }
            }

            foreach ([
                [$student, route('student.courses.player', $enrollment)],
                [$student, route('courses.show', $course->slug)],
                [$student, route('cms.page', 'about')],
                [$course->instructor, route('instructor.courses.edit', ['course' => $course, 'tab' => 'curriculum'])],
                [$course->instructor, route('instructor.lessons.edit', $lesson)],
                [$admin, route('admin.courses.show', $course)],
                [$admin, route('admin.users.show', $student)],
                [$admin, route('admin.cms-pages.edit', \App\Models\CmsPage::first())],
                [$admin, route('admin.announcements.create')],
            ] as [$user, $url]) {
                $response = $this->withSession(['locale' => $locale])->actingAs($user)->get($url);
                if ($response->status() >= 400) {
                    $failures[] = "[$locale] $url => " . $response->status() . ' ' . substr((string) $response->exception?->getMessage(), 0, 200);
                }
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_pages_are_actually_translated(): void
    {
        $this->seed();

        $this->withSession(['locale' => 'zh'])->get('/courses')->assertOk()->assertSee('课程');
        $this->withSession(['locale' => 'ar'])->get('/')->assertOk()
            ->assertSee('dir="rtl"', false)->assertSee('bootstrap.rtl.min.css', false)->assertSee('من نحن');
        $this->withSession(['locale' => 'es'])->get('/page/about')->assertOk()->assertSee('Acerca de ACADEXA');
        $this->withSession(['locale' => 'pt'])->get('/login')->assertOk()->assertSee('Entrar');
        $this->withSession(['locale' => 'fr'])->get('/')->assertOk()->assertSee('Débutant');
    }

    public function test_validation_messages_follow_the_language(): void
    {
        $this->withSession(['locale' => 'es'])->post('/login', [])->assertSessionHasErrors(['email' => 'El campo correo electrónico es obligatorio.']);
    }
}
