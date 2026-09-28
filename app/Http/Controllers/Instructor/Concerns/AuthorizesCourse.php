<?php

namespace App\Http\Controllers\Instructor\Concerns;

use App\Models\Course;

trait AuthorizesCourse
{
    /** The course owner or an admin may edit a course and its content. */
    protected function authorizeCourse(Course $course): void
    {
        $user = auth()->user();
        abort_if($course->instructor_id !== $user->id && ! $user->isAdmin(), 403, __('lms.unauthorized'));
    }

    /** Structural edits on a course under review would change what the admin is reviewing. */
    protected function ensureEditable(Course $course): void
    {
        abort_if($course->status === 'pending' && ! auth()->user()->isAdmin(), 423, __('lms.course_locked_pending'));
    }
}
