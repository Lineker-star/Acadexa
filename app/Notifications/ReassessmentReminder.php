<?php

namespace App\Notifications;

use App\Models\Enrollment;

/** Invites a student who finished a course to re-evaluate their knowledge (retention check). */
class ReassessmentReminder extends LmsNotification
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
        return __('learn.notif_reassess_subject');
    }

    protected function line(object $notifiable): string
    {
        return __('learn.notif_reassess_line', ['course' => $this->enrollment->course->title()]);
    }

    protected function url(object $notifiable): string
    {
        return route('student.courses.results', $this->enrollment);
    }

    protected function icon(): string
    {
        return 'bi-arrow-repeat';
    }
}
