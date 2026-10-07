<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Setting;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Storage;

/**
 * Lesson quiz: automatic mark out of 10 with the answers given back.
 * Module exercise: open questions, the instructor's detailed answers are shown after hand-in.
 * Final evaluation: 70/100 to pass and receive the certificate.
 */
class AssessmentGradingTest extends LmsTestCase
{
    public function test_lesson_quiz_is_marked_out_of_ten_and_gives_the_answers_back(): void
    {
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $path = $this->addAssessmentPath($course);
        $this->enroll($student, $course);
        $lesson = $this->lessonsOf($course)->first();
        ['quiz' => $quiz, 'right' => $right] = $path['lessons'][$lesson->id];
        // Even when the instructor turned "show the answers" off, a lesson quiz gives them back.
        $quiz->update(['show_correct_answers' => false]);
        $quiz->questions()->first()->update(['explanation' => 'Parce que.']);
        $firstQuestion = array_key_first($right);

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => $this->answers($right, 6)])
            ->assertOk()
            ->assertJson(['passed' => false, 'grade' => ['value' => 6, 'out_of' => 10], 'correct' => 6, 'total' => 10])
            ->assertJsonPath("review.$firstQuestion.answer", [$right[$firstQuestion]])
            ->assertJsonPath("review.$firstQuestion.explanation", 'Parce que.');

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $quiz), ['answers' => $this->answers($right, 7)])
            ->assertOk()->assertJson(['passed' => true, 'grade' => ['value' => 7, 'out_of' => 10]]);
    }

    public function test_module_exercise_uses_open_questions_with_detailed_answers(): void
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser();
        $course = $this->makeCourse($instructor, ['text' => 1], ['status' => 'draft']);
        $module = $course->modules()->first();
        $lesson = $this->lessonsOf($course)->first();

        // The instructor writes each question with its detailed answer…
        $this->actingAs($instructor)->get(route('instructor.assessments.module', $module))->assertRedirect();
        $exercise = $module->exam()->first();
        $this->actingAs($instructor)->post(route('instructor.questions.store', $exercise), ['question' => 'Définissez le bilan.'])
            ->assertSessionHasErrors('model_answer');
        $this->actingAs($instructor)->post(route('instructor.questions.store', $exercise), [
            'question' => 'Définissez le bilan.', 'model_answer' => 'Le bilan présente l’actif et le passif à une date donnée.',
        ])->assertSessionHasNoErrors();
        // …or imports several at once.
        $text = "Q2 ?\nRéponse détaillée numéro deux.\n\nQ3 ?\n> Réponse détaillée numéro trois.\n\nQ4 ?\nRéponse quatre, ligne 1.\nLigne 2.\n\nQ5 ?\nRéponse détaillée numéro cinq.";
        $this->actingAs($instructor)->post(route('instructor.questions.import', $exercise), ['questions_text' => $text])->assertSessionHasNoErrors();

        $questions = $exercise->questions()->get();
        $this->assertCount(5, $questions);
        $this->assertTrue($questions->every(fn (QuizQuestion $q) => $q->isOpen() && filled($q->model_answer)));
        $this->assertSame("Réponse quatre, ligne 1.\nLigne 2.", $questions[3]->model_answer);
        $this->assertTrue($exercise->fresh()->isReady());
        $this->actingAs($instructor)->get(route('instructor.quizzes.edit', $exercise))->assertOk()
            ->assertSee('openQuestionModal', false)->assertSee(__('learn.model_answer'));

        // The student answers in writing.
        $course->update(['status' => 'published']);
        $enrollment = $this->enroll($student, $course);
        $this->actingAs($student)->postJson(route('student.lesson.complete', $lesson))->assertOk();
        $this->actingAs($student)->get(route('student.courses.player', $enrollment) . '?assessment=' . $exercise->id)->assertOk()
            ->assertSee('data-open-answer', false)->assertDontSee('Le bilan présente');

        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'Ma réponse rédigée.'])->all();
        $incomplete = $answers;
        $incomplete[$questions[1]->id] = '';
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $exercise), ['answers' => $incomplete])
            ->assertStatus(422)->assertJsonPath('unanswered', [$questions[1]->id]);

        // Handing in validates the exercise and returns the detailed answers.
        $this->actingAs($student)->postJson(route('student.quiz.attempt', $exercise), ['answers' => $answers])
            ->assertOk()->assertJson(['passed' => true, 'open' => true, 'grade' => null])
            ->assertJsonPath('review.' . $questions[0]->id . '.model_answer', 'Le bilan présente l’actif et le passif à une date donnée.')
            ->assertJsonPath('progress.completed', true);

        // Afterwards the student sees their answers next to the corrections; the instructor reads them too.
        $this->actingAs($student)->get(route('student.courses.player', $enrollment) . '?assessment=' . $exercise->id)->assertOk()
            ->assertSee('Ma réponse rédigée.')->assertSee('Le bilan présente');
        $this->actingAs($instructor)->get(route('instructor.students.show', [$course, $enrollment]))->assertOk()
            ->assertSee(__('learn.student_answers'))->assertSee('Ma réponse rédigée.');
    }

    public function test_final_evaluation_at_70_out_of_100_delivers_the_certificate(): void
    {
        Storage::fake('public');
        $student = $this->makeUser('student', ['name' => 'Aïcha Mbarga']);
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $final = Quiz::create(['scope' => Quiz::SCOPE_COURSE, 'course_id' => $course->id, 'passing_score' => 70]);
        $right = $this->addQuestions($final, 20);
        $this->enroll($student, $course);
        $this->actingAs($student)->postJson(route('student.lesson.complete', $this->lessonsOf($course)->first()))->assertOk();

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final), ['answers' => $this->answers($right, 13)])
            ->assertJson(['passed' => false, 'grade' => ['value' => 65, 'out_of' => 100]]);
        $this->assertFalse($student->certificates()->exists());

        $this->actingAs($student)->postJson(route('student.quiz.attempt', $final), ['answers' => $this->answers($right, 14)])
            ->assertJson(['passed' => true, 'grade' => ['value' => 70, 'out_of' => 100]])
            ->assertJsonPath('progress.certificate_issued', true);

        // The certificate presents ACADEXA as part of the institute.
        $certificate = $student->certificates()->with(['user', 'course.translations', 'course.finalExam'])->first();
        app()->setLocale('fr');
        $html = view('certificates.pdf', ['certificate' => $certificate, 'sig_name' => 'X', 'sig_title' => 'Y'])->render();
        $this->assertStringContainsString('Aïcha Mbarga', $html);
        $this->assertStringContainsString('Institut de Formation Professionnelle ZTF', $html);
        $this->assertStringContainsString(__('learn.cert_part_of', ['institution' => 'Institut de Formation Professionnelle ZTF']), html_entity_decode($html));
        $this->assertStringContainsString('70/100', $html);

        // A name typed by the admin replaces the default.
        Setting::set('cert_institution', 'Mon Institut');
        $this->assertStringContainsString('Mon Institut', view('certificates.pdf', ['certificate' => $certificate, 'sig_name' => 'X', 'sig_title' => 'Y'])->render());

        // A PDF made with an earlier design is rebuilt when the student downloads it again.
        $current = $certificate->pdf_path;
        $this->assertStringEndsWith('_v' . CertificateService::TEMPLATE_VERSION . '.pdf', $current);
        Storage::disk('public')->move('certificates/' . $current, 'certificates/old-design.pdf');
        $certificate->update(['pdf_path' => 'old-design.pdf']);
        $this->actingAs($student)->get(route('student.certificates.download', $certificate))->assertOk()->assertDownload();
        $this->assertSame($current, $certificate->fresh()->pdf_path);
        Storage::disk('public')->assertExists('certificates/' . $current);
        Storage::disk('public')->assertMissing('certificates/old-design.pdf');
    }
}
