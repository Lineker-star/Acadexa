<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->with(['course.translations', 'course.instructor', 'course.category.translations'])
            ->latest()
            ->paginate(12);

        return view('student.courses.index', compact('enrollments'));
    }

    public function enroll(Request $request, Course $course)
    {
        $user = $request->user();

        abort_if($course->status !== 'published', 404);

        if ($user->enrollments()->where('course_id', $course->id)->exists()) {
            return redirect()->route('student.courses.player',
                $user->enrollments()->where('course_id', $course->id)->first()
            )->with('info', __('messages.already_enrolled'));
        }

        $enrollment = Enrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'enrolled_at' => now(),
        ]);

        return redirect()->route('student.courses.player', $enrollment)
            ->with('success', __('messages.enrolled_success'));
    }

    public function player(Request $request, Enrollment $enrollment)
    {
        abort_if($enrollment->user_id !== $request->user()->id, 403);

        $enrollment->load([
            'course.translations',
            'course.instructor',
            'course.modules.lessons.translations',
            'course.modules.lessons.quiz.questions.options',
            'lessonProgress',
        ]);

        $completedLessonIds = $enrollment->lessonProgress->pluck('lesson_id')->toArray();

        // Get current lesson (last accessed or first)
        $currentLesson = null;
        if ($enrollment->last_lesson_id) {
            foreach ($enrollment->course->modules as $module) {
                $currentLesson = $module->lessons->find($enrollment->last_lesson_id);
                if ($currentLesson) break;
            }
        }
        if (! $currentLesson) {
            $currentLesson = $enrollment->course->modules->first()?->lessons->first();
        }

        if ($currentLesson) {
            $currentLesson->load(['translations', 'quiz.questions.options', 'comments.user', 'comments.replies.user']);
        }

        return view('student.courses.player', compact('enrollment', 'completedLessonIds', 'currentLesson'));
    }
}
