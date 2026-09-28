<?php

namespace App\Notifications;

use App\Models\Course;

/** Sent to the instructor when an admin approves or rejects a submitted course. */
class CourseReviewed extends LmsNotification
{
    public function __construct(public Course $course, public bool $approved, public ?string $feedback = null)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return $this->approved ? __('lms.notif_course_approved_subject') : __('lms.notif_course_rejected_subject');
    }

    protected function line(object $notifiable): string
    {
        $key = $this->approved ? 'lms.notif_course_approved_line' : 'lms.notif_course_rejected_line';
        return __($key, ['course' => $this->course->title()]);
    }

    protected function extraLines(object $notifiable): array
    {
        return $this->feedback ? [__('lms.notif_feedback', ['feedback' => $this->feedback])] : [];
    }

    protected function url(object $notifiable): string
    {
        return route('instructor.courses.edit', $this->course);
    }

    protected function icon(): string
    {
        return $this->approved ? 'bi-check-circle' : 'bi-x-circle';
    }
}
