<?php

namespace App\Notifications;

use App\Models\Enrollment;
use App\Models\QuizAttempt;

/** Tells the instructor that a student scored lower than before on the final evaluation. */
class KnowledgeRegression extends LmsNotification
{
    public function __construct(public QuizAttempt $attempt, public ?float $delta)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('learn.notif_regression_subject');
    }

    protected function line(object $notifiable): string
    {
        return __('learn.notif_regression_line', [
            'student' => $this->attempt->user->name,
            'course'  => $this->attempt->quiz->ownerCourse()->title(),
            'score'   => (float) $this->attempt->score,
            'delta'   => abs((float) $this->delta),
        ]);
    }

    protected function url(object $notifiable): string
    {
        $course = $this->attempt->quiz->ownerCourse();
        $enrollment = Enrollment::where('course_id', $course->id)->where('user_id', $this->attempt->user_id)->first();
        return $enrollment
            ? route('instructor.students.show', [$course, $enrollment])
            : route('instructor.students.index', $course);
    }

    protected function icon(): string
    {
        return 'bi-graph-down-arrow';
    }
}
