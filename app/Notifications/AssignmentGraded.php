<?php

namespace App\Notifications;

use App\Models\AssignmentSubmission;
use App\Models\Enrollment;

/** Sent to the student when the instructor grades an assignment. */
class AssignmentGraded extends LmsNotification
{
    public function __construct(public AssignmentSubmission $submission)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('lms.notif_assignment_graded_subject');
    }

    protected function line(object $notifiable): string
    {
        return __('lms.notif_assignment_graded_line', [
            'lesson' => $this->submission->lesson->title(),
            'score'  => rtrim(rtrim((string) $this->submission->score, '0'), '.'),
            'max'    => $this->submission->lesson->assignment_max_score,
        ]);
    }

    protected function extraLines(object $notifiable): array
    {
        return $this->submission->feedback ? [__('lms.notif_feedback', ['feedback' => $this->submission->feedback])] : [];
    }

    protected function url(object $notifiable): string
    {
        $enrollment = Enrollment::where('user_id', $this->submission->user_id)
            ->where('course_id', $this->submission->lesson->module->course_id)
            ->first();

        return $enrollment
            ? route('student.courses.player', $enrollment) . '?lesson=' . $this->submission->lesson_id
            : route('student.courses.index');
    }

    protected function icon(): string
    {
        return 'bi-journal-check';
    }
}
