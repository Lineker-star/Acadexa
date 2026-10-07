<?php

namespace Tests\Feature;

use App\Http\Controllers\Instructor\QuizController as InstructorQuizController;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Notifications\KnowledgeRegression;
use App\Notifications\ReassessmentReminder;
use Illuminate\Support\Facades\Notification;

/**
 * Assessment path: a quiz (>= 10 questions, 7/10 to pass) after every lesson, an exercise after
 * every module, a final evaluation after the course — and knowledge tracking built on the final
 * evaluation (placement test, end of course, re-evaluations).
 */
class AssessmentPathTest extends LmsTestCase
{
    public function test_a_lesson_with_a_quiz_is_validated_by_passing_it_with_seven_out_of_ten(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 2]);
        $path = $this->addAssessmentPath($course);
        $enrollment = $this->enroll($student, $course);
        [$first] = $this->lessonsOf($course)->all();
        ['quiz' => $quiz, 'right' => $right] = $path['lessons'][$first->id];

        // The quiz is shown under the lesson in the player, with the 7/10 rule.
        $this->actingAs($student)->get(route('student.courses.player', $enrollment) . '?lesson=' . $first->id)->assertOk()
            ->assertSee('id="lessonQuiz"', false)->assertSee('data-scope="lesson"', false)->assertDontSee('id="markCompleteBtn"', false);

        // No "mark as complete" button any more: the quiz decides.
        $this->actingAs($student)->postJson(route('student.lesson.complete', $first))->assertStatus(422);

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => $this->answers($right, 6)])
            ->assertOk()->assertJson(['passed' => false, 'score' => 60, 'passing_score' => 70]);
        $this->assertSame(0, $enrollment->lessonProgress()->count());

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => $this->answers($right, 7)])
            ->assertOk()->assertJson(['passed' => true, 'score' => 70, 'correct' => 7, 'total' => 10]);
        $this->assertSame(1, $enrollment->lessonProgress()->count());
    }

    public function test_the_pass_mark_can_never_be_lowered_below_the_platform_rule(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['text' => 1], ['status' => 'draft']);
        $quiz = $this->addAssessmentPath($course)['modules'][$course->modules()->first()->id]['quiz'];

        $this->actingAs($instructor)->put(route('instructor.quizzes.update', $quiz), ['passing_score' => 50])
            ->assertSessionHasErrors('passing_score');
        // An old quiz stored with a lower score is still graded at 70 %.
        $quiz->update(['passing_score' => 40]);
        $this->assertSame(70, $quiz->fresh()->effectivePassingScore());
    }

    public function test_module_exercise_and_final_evaluation_complete_the_path_before_the_certificate(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $path = $this->addAssessmentPath($course);
        $enrollment = $this->enroll($student, $course);
        $lesson = $this->lessonsOf($course)->first();
        $exam = $path['modules'][$lesson->module_id];
        $final = $path['final'];

        // Exercise and final evaluation are locked until what comes before is done.
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $exam['quiz']), ['answers' => $this->answers($exam['right'], 10)])->assertStatus(423);
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final['quiz']), ['answers' => $this->answers($final['right'], 20)])->assertStatus(423);

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $path['lessons'][$lesson->id]['quiz']), ['answers' => $this->answers($path['lessons'][$lesson->id]['right'], 10)])
            ->assertJsonPath('progress.progress', 33.33);

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $exam['quiz']), ['answers' => $this->answers($exam['right'], 8)])
            ->assertJson(['passed' => true])->assertJsonPath('progress.progress', 66.67);
        $this->assertFalse($student->certificates()->exists());

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final['quiz']), ['answers' => $this->answers($final['right'], 16)])
            ->assertJson(['passed' => true])->assertJsonPath('progress.certificate_issued', true);
        $this->assertEquals(100, (float) $enrollment->fresh()->progress_percent);

        // The player shows every step, including the assessments.
        $this->actingAs($student)->get(route('student.courses.player', $enrollment) . '?assessment=' . $final['quiz']->id)
            ->assertOk()->assertSee(__('learn.final_evaluation'));
    }

    public function test_sequential_courses_lock_the_next_module_until_the_exercise_is_passed(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1], ['is_sequential' => true]);
        $second = \App\Models\Module::create(['course_id' => $course->id, 'order' => 2, 'title' => 'Module 2', 'duration_hours' => 2]);
        $nextLesson = \App\Models\Lesson::create(['module_id' => $second->id, 'order' => 1, 'type' => 'text', 'duration_minutes' => 5]);
        $path = $this->addAssessmentPath($course->fresh());
        $this->enroll($student, $course);
        $first = $this->lessonsOf($course)->first();

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $path['lessons'][$first->id]['quiz']), ['answers' => $this->answers($path['lessons'][$first->id]['right'], 10)])
            ->assertJson(['passed' => true]);
        // Lesson of module 2: still locked while the module 1 exercise is not passed.
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $path['lessons'][$nextLesson->id]['quiz']), ['answers' => $this->answers($path['lessons'][$nextLesson->id]['right'], 10)])
            ->assertStatus(423);

        $exam = $path['modules'][$first->module_id];
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $exam['quiz']), ['answers' => $this->answers($exam['right'], 10)])->assertJson(['passed' => true]);
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $path['lessons'][$nextLesson->id]['quiz']), ['answers' => $this->answers($path['lessons'][$nextLesson->id]['right'], 10)])
            ->assertOk()->assertJson(['passed' => true]);
    }

    public function test_knowledge_progression_and_regression_are_measured_with_the_final_evaluation(): void
    {
        Notification::fake();
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $course = $this->makeCourse($instructor, ['text' => 1]);
        $path = $this->addAssessmentPath($course);
        $enrollment = $this->enroll($student, $course);
        $final = $path['final'];
        $lesson = $this->lessonsOf($course)->first();

        // Placement test before studying: allowed once, does not count, answers hidden.
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final['quiz']), ['answers' => $this->answers($final['right'], 6), 'mode' => 'diagnostic'])
            ->assertOk()->assertJson(['mode' => 'diagnostic', 'score' => 30, 'progress' => null])
            ->assertJsonPath('review.' . array_key_first($final['right']) . '.answer', null);
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final['quiz']), ['answers' => [], 'mode' => 'diagnostic'])->assertStatus(422);
        // A retake is only possible after passing.
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final['quiz']), ['answers' => [], 'mode' => 'retake'])->assertStatus(422);

        // Study, then pass the final evaluation: +50 points.
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $path['lessons'][$lesson->id]['quiz']), ['answers' => $this->answers($path['lessons'][$lesson->id]['right'], 9)]);
        $exam = $path['modules'][$lesson->module_id];
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $exam['quiz']), ['answers' => $this->answers($exam['right'], 9)]);
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final['quiz']), ['answers' => $this->answers($final['right'], 16)])
            ->assertJson(['passed' => true, 'knowledge' => ['baseline' => 30, 'current' => 80, 'gain' => 50, 'trend' => 'progress']]);

        // Re-evaluation too early: refused (cooldown).
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final['quiz']), ['answers' => [], 'mode' => 'retake'])->assertStatus(422);

        // A month later, the student remembers less: regression, the instructor is told.
        \App\Models\Setting::set('trial_days', '365');
        $this->travel(31)->days();
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final['quiz']), ['answers' => $this->answers($final['right'], 12), 'mode' => 'retake'])
            ->assertOk()->assertJson(['mode' => 'retake', 'knowledge' => ['current' => 60, 'delta' => -20, 'trend' => 'regression']]);
        Notification::assertSentTo($instructor, KnowledgeRegression::class);
        // The certificate and the progress are not affected by a re-evaluation.
        $this->assertEquals(100, (float) $enrollment->fresh()->progress_percent);

        // What the instructor sees.
        $this->actingAs($instructor)->get(route('instructor.students.knowledge', $course))->assertOk()
            ->assertSee($student->name)->assertSee(__('learn.knowledge_level_intermediate'));
        $this->actingAs($instructor)->get(route('instructor.students.show', [$course, $enrollment]))->assertOk()->assertSee(__('learn.trend_regression'));
        $this->actingAs($instructor)->get(route('instructor.dashboard'))->assertOk()->assertSee(__('learn.students_in_regression'));
        // And the student.
        $this->actingAs($student)->get(route('student.courses.results', $enrollment))->assertOk()->assertSee(__('learn.knowledge_evolution'));
        // Mastery per module comes from the questions tagged with the module.
        $this->assertEquals([$lesson->module_id => 60.0], QuizAttempt::where('mode', Quiz::MODE_RETAKE)->first()->module_scores);
    }

    public function test_reassessment_reminder_is_sent_some_time_after_the_final_evaluation(): void
    {
        Notification::fake();
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $final = $this->addAssessmentPath($course)['final']['quiz'];
        $enrollment = $this->enroll($student, $course);
        $enrollment->update(['completed_at' => now(), 'progress_percent' => 100]);
        QuizAttempt::create(['user_id' => $student->id, 'quiz_id' => $final->id, 'mode' => 'standard', 'score' => 80, 'passed' => true, 'attempted_at' => now()]);

        $this->artisan('acadexxa:reassessment-reminders')->assertSuccessful();
        Notification::assertNothingSent();

        $this->travel(config('lms.assessment.reassess_after_days') + 1)->days();
        $this->artisan('acadexxa:reassessment-reminders')->assertSuccessful();
        Notification::assertSentTo($student, ReassessmentReminder::class);
    }

    public function test_instructor_builds_the_path_and_imports_questions_from_text(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['video' => 1], ['status' => 'draft']);
        $lesson = $this->lessonsOf($course)->first();
        $module = $course->modules()->first();

        $this->actingAs($instructor)->get(route('instructor.assessments.lesson', $lesson))->assertRedirect();
        $this->actingAs($instructor)->get(route('instructor.assessments.module', $module))->assertRedirect();
        $this->actingAs($instructor)->get(route('instructor.assessments.final', $course))->assertRedirect();
        $final = $course->finalExam()->first();
        $this->assertFalse($final->show_correct_answers);

        $text = "What is 2 + 2?\n- 3\n* 4\n> Basic arithmetic.\n\nEven numbers?\n* 2\n- 3\n* 4\n\nBroken block without answer\n- a\n- b";
        $this->actingAs($instructor)->post(route('instructor.questions.import', $final), ['questions_text' => $text])
            ->assertSessionHasErrors('questions_text');
        $this->assertSame(0, $final->questions()->count());

        $this->actingAs($instructor)->post(route('instructor.questions.import', $final), ['questions_text' => substr($text, 0, strpos($text, "\n\nBroken"))])
            ->assertRedirect(route('instructor.quizzes.edit', $final));
        $questions = $final->questions()->with('options')->get();
        $this->assertSame(['single', 'multiple'], $questions->pluck('type')->all());
        $this->assertSame('Basic arithmetic.', $questions[0]->explanation);
        $this->assertSame(['4'], $questions[0]->options->where('is_correct', true)->pluck('option_text')->values()->all());

        // Tagging a final-evaluation question with a module of another course is refused.
        $other = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $this->actingAs($instructor)->post(route('instructor.questions.store', $final), [
            'question' => 'Q', 'type' => 'single', 'module_id' => $other->modules()->first()->id,
            'options' => [['text' => 'a'], ['text' => 'b']], 'correct' => [0],
        ])->assertSessionHasErrors('module_id');

        $this->actingAs($instructor)->get(route('instructor.quizzes.edit', $final))->assertOk()->assertSee(__('learn.assessment_plan'));
        $this->actingAs($instructor)->get(route('instructor.courses.edit', ['course' => $course, 'tab' => 'assessments']))->assertOk();
        [$parsed, $errors] = InstructorQuizController::parseQuestions("1. Q?\n- a\n+ b");
        $this->assertSame([], $errors);
        $this->assertSame('Q?', $parsed[0]['question']);
    }

    public function test_offline_package_carries_the_path_without_revealing_answers(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $path = $this->addAssessmentPath($course);
        $enrollment = $this->enroll($student, $course);
        $lesson = $this->lessonsOf($course)->first();

        $response = $this->actingAs($student)->getJson(route('offline.package', $enrollment))->assertOk();
        $response->assertJsonPath('modules.0.lessons.0.quiz_check', true)
            ->assertJsonPath('modules.0.lessons.0.completable', false)
            ->assertJsonPath('modules.0.lessons.0.quiz.passing_score', 70)
            ->assertJsonPath('modules.0.exam.id', $path['modules'][$lesson->module_id]['quiz']->id)
            ->assertJsonPath('final.questions', 20);
        $this->assertStringNotContainsString('is_correct', $response->getContent());

        // The hash lets the app check an answer offline; the server grades it on sync.
        $question = $response->json('modules.0.lessons.0.quiz.questions.0');
        $rightId = $path['lessons'][$lesson->id]['right'][$question['id']];
        $this->assertSame(hash('sha256', $response->json('hash_salt') . '|' . $question['id'] . '|' . $rightId), $question['check']);

        $this->actingAs($student)->postJson(route('offline.sync'), ['events' => [
            ['id' => 'q1', 'type' => 'quiz', 'quiz_id' => $path['lessons'][$lesson->id]['quiz']->id, 'answers' => $this->answers($path['lessons'][$lesson->id]['right'], 8)],
        ]])->assertOk()->assertJsonPath('results.0.ok', true)->assertJsonPath('results.0.data.passed', true);
        $this->assertSame(1, $enrollment->lessonProgress()->count());
    }
}
