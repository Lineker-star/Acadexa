<?php

namespace App\Notifications;

use App\Models\Setting;

class WelcomeNotification extends LmsNotification
{
    protected function subject(object $notifiable): string
    {
        return __('lms.notif_welcome_subject', ['site' => Setting::get('site_name', 'ACADEXA')]);
    }

    protected function line(object $notifiable): string
    {
        return trans_choice('lms.notif_welcome_line', (int) Setting::get('trial_days', 30), [
            'days' => (int) Setting::get('trial_days', 30),
        ]);
    }

    protected function url(object $notifiable): string
    {
        return route('courses.index');
    }

    protected function actionText(object $notifiable): string
    {
        return __('lms.notif_browse_courses');
    }

    protected function icon(): string
    {
        return 'bi-stars';
    }

    /** Always e-mailed: the user has not chosen preferences yet. */
    protected function forceMail(): bool
    {
        return true;
    }
}
