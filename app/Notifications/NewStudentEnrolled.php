<?php

namespace App\Notifications;

use App\Models\Enrollment;

/** Tells an instructor that a student joined one of their courses. */
class NewStudentEnrolled extends LmsNotification
{
    public function __construct(public Enrollment $enrollment)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('learn.notif_new_student_subject', ['course' => $this->enrollment->course->title()]);
    }

    protected function line(object $notifiable): string
    {
        return __('learn.notif_new_student_line', ['student' => $this->enrollment->user->name, 'course' => $this->enrollment->course->title()]);
    }

    protected function url(object $notifiable): string
    {
        return route('instructor.students.index', $this->enrollment->course_id);
    }

    protected function icon(): string
    {
        return 'bi-person-plus';
    }
}
