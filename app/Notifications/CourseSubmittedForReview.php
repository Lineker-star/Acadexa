<?php

namespace App\Notifications;

use App\Models\Course;

/** Sent to admins when an instructor submits a course. */
class CourseSubmittedForReview extends LmsNotification
{
    public function __construct(public Course $course)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('lms.notif_course_submitted_subject');
    }

    protected function line(object $notifiable): string
    {
        return __('lms.notif_course_submitted_line', [
            'course'     => $this->course->title(),
            'instructor' => $this->course->instructor->name,
        ]);
    }

    protected function url(object $notifiable): string
    {
        return route('admin.courses.show', $this->course);
    }

    protected function icon(): string
    {
        return 'bi-hourglass-split';
    }
}
