<?php

namespace App\Notifications;

use App\Models\Enrollment;

/** Invites a student who has not opened a course for a while to pick it up where they stopped. */
class InactivityReminder extends LmsNotification
{
    public function __construct(public Enrollment $enrollment)
    {
        parent::__construct();
    }

    protected function category(): string
    {
        return self::CATEGORY_NEWS;
    }

    protected function subject(object $notifiable): string
    {
        return __('learn.notif_inactive_subject', ['course' => $this->enrollment->course->title()]);
    }

    protected function line(object $notifiable): string
    {
        return __('learn.notif_inactive_line', [
            'course'  => $this->enrollment->course->title(),
            'percent' => (int) round((float) $this->enrollment->progress_percent),
        ]);
    }

    protected function url(object $notifiable): string
    {
        return route('student.courses.player', $this->enrollment);
    }

    protected function actionText(object $notifiable): string
    {
        return __('messages.continue_learning');
    }

    protected function icon(): string
    {
        return 'bi-alarm';
    }
}
