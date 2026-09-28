<?php

namespace App\Notifications;

use App\Models\AssignmentSubmission;

/** Sent to the instructor when a student hands in an assignment. */
class AssignmentSubmitted extends LmsNotification
{
    public function __construct(public AssignmentSubmission $submission)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('lms.notif_assignment_submitted_subject');
    }

    protected function line(object $notifiable): string
    {
        return __('lms.notif_assignment_submitted_line', [
            'student' => $this->submission->user->name,
            'lesson'  => $this->submission->lesson->title(),
        ]);
    }

    protected function url(object $notifiable): string
    {
        return route('instructor.submissions.show', $this->submission);
    }

    protected function icon(): string
    {
        return 'bi-clipboard-check';
    }
}
