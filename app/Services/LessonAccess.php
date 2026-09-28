<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;

/**
 * Who may open a lesson and its media:
 *  - anyone (guests included) for free-preview lessons of published courses,
 *  - the course's instructor and admins,
 *  - enrolled users whose trial/subscription is active.
 */
class LessonAccess
{
    public function canView(?User $user, Lesson $lesson): bool
    {
        $course = $lesson->module->course;

        if ($lesson->is_free_preview && $course->status === 'published') {
            return true;
        }

        if (! $user || ! $user->canAccess()) {
            return false;
        }

        if ($user->isAdmin() || $course->instructor_id === $user->id) {
            return true;
        }

        return $user->isTrialActive()
            && Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists();
    }

    /** Course books: the course's instructor, admins and enrolled users with active access. */
    public function canViewBook(?User $user, Book $book): bool
    {
        if (! $user || ! $user->canAccess()) {
            return false;
        }
        $course = $book->course;
        if ($user->isAdmin() || $course->instructor_id === $user->id) {
            return true;
        }
        return $user->isTrialActive()
            && Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists();
    }
}
