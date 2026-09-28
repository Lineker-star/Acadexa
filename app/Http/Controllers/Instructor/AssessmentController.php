<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;

/**
 * Entry points of the assessment path: the quiz after a lesson, the exercise closing a
 * module and the course's final evaluation. Each one is created on first use and edited
 * with the same question editor.
 */
class AssessmentController extends Controller
{
    use AuthorizesCourse;

    public function lessonQuiz(Lesson $lesson)
    {
        $course = $lesson->module->course;
        $this->authorizeCourse($course);
        abort_if($lesson->type === 'assignment', 404);

        return $this->open($course, $lesson->quiz, fn () => Quiz::create([
            'scope' => Quiz::SCOPE_LESSON, 'lesson_id' => $lesson->id, 'passing_score' => config('lms.assessment.pass_percent'),
        ]));
    }

    public function moduleExercise(Module $module)
    {
        $course = $module->course;
        $this->authorizeCourse($course);

        return $this->open($course, $module->exam, fn () => Quiz::create([
            'scope' => Quiz::SCOPE_MODULE, 'module_id' => $module->id, 'passing_score' => config('lms.assessment.pass_percent'),
        ]));
    }

    public function finalEvaluation(Course $course)
    {
        $this->authorizeCourse($course);

        // The final evaluation is retaken later to measure knowledge: answers stay hidden by default.
        return $this->open($course, $course->finalExam, fn () => Quiz::create([
            'scope' => Quiz::SCOPE_COURSE, 'course_id' => $course->id,
            'passing_score' => config('lms.assessment.pass_percent'), 'show_correct_answers' => false,
        ]));
    }

    public function edit(Quiz $quiz)
    {
        $course = $quiz->ownerCourse();
        $this->authorizeCourse($course);

        $quiz->load(['questions.options', 'questions.module.translations', 'lesson.translations', 'module.translations']);
        $course->load(['translations', 'modules.translations', 'modules.lessons.translations', 'modules.lessons.quiz', 'modules.exam', 'finalExam']);
        $locked = $course->status === 'pending' && ! auth()->user()->isAdmin();

        return view('instructor.quizzes.edit', compact('quiz', 'course', 'locked'));
    }

    private function open(Course $course, ?Quiz $existing, \Closure $create)
    {
        if (! $existing) {
            $this->ensureEditable($course);
            $existing = $create();
        }
        return redirect()->route('instructor.quizzes.edit', $existing);
    }
}
