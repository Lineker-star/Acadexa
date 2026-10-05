<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Every e-mail (notifications, two-factor codes…) can go through Brevo's HTTP API: MAIL_MAILER=brevo.
        \Illuminate\Support\Facades\Mail::extend('brevo', fn () => new \App\Mail\BrevoTransport(config('services.brevo.key')));

        // Bootstrap 5 pagination markup (the UI uses Bootstrap); its labels are translated in lang/*.json.
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Queued notifications/e-mails are rendered in the recipient's language (User::preferredLocale),
        // including dates.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Foundation\Events\LocaleUpdated::class, function ($event) {
            \Illuminate\Support\Carbon::setLocale($event->locale);
        });

        Gate::define('admin-access', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('instructor-access', function ($user) {
            return in_array($user->role, ['instructor', 'admin', 'super_admin'])
                && $user->instructor_status === 'confirmed';
        });

        Gate::define('super-admin-access', function ($user) {
            return $user->role === 'super_admin';
        });

        // Share settings globally to all views
        view()->composer('*', function ($view) {
            $settings = Cache::remember('site_settings', 3600, function () {
                return Setting::pluck('value', 'key')->toArray();
            });
            $view->with('siteSettings', $settings);
        });
    }
}
