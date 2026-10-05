<?php

namespace App\Notifications;

use App\Models\Enrollment;

/** Confirms to a student that they are enrolled in a course, with a link to start. */
class EnrollmentConfirmed extends LmsNotification
{
    public function __construct(public Enrollment $enrollment)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('learn.notif_enrolled_subject', ['course' => $this->enrollment->course->title()]);
    }

    protected function line(object $notifiable): string
    {
        return __('learn.notif_enrolled_line', ['course' => $this->enrollment->course->title()]);
    }

    protected function extraLines(object $notifiable): array
    {
        return [__('learn.notif_enrolled_tip')];
    }

    protected function url(object $notifiable): string
    {
        return route('student.courses.player', $this->enrollment);
    }

    protected function actionText(object $notifiable): string
    {
        return __('messages.start_learning');
    }

    protected function icon(): string
    {
        return 'bi-play-circle';
    }
}
