<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseTranslation;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonTranslation;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class LmsTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('trial_days', '30');
    }

    protected function makeUser(string $role = 'student', array $attrs = []): User
    {
        $user = User::create(array_merge([
            'name'               => ucfirst($role) . ' ' . Str::random(4),
            'email'              => Str::random(8) . '@example.test',
            'password'           => 'Password123!',
            'role'               => $role,
            'instructor_status'  => $role === 'instructor' ? 'confirmed' : 'none',
            'trial_started_at'   => now(),
            'is_active'          => true,
            'email_verified_at'  => now(),
            'preferred_language' => 'fr',
        ], $attrs));
        // Not mass assignable: verified accounts unless the test says otherwise.
        $user->forceFill(['email_verified_at' => array_key_exists('email_verified_at', $attrs) ? $attrs['email_verified_at'] : now()])->save();

        return $user;
    }

    protected function makeCategory(): Category
    {
        return Category::create(['name' => 'Catégorie', 'slug' => 'cat-' . Str::random(5), 'is_active' => true, 'order' => 1]);
    }

    /**
     * Published course with one module and the given lessons (type => count),
     * e.g. ['text' => 2, 'quiz' => 1].
     */
    protected function makeCourse(User $instructor, array $lessons = ['text' => 2], array $attrs = []): Course
    {
        $course = Course::create(array_merge([
            'instructor_id'  => $instructor->id,
            'category_id'    => $this->makeCategory()->id,
            'level'          => 'beginner',
            'price'          => 0,
            'status'         => 'published',
            'slug'           => 'course-' . Str::lower(Str::random(6)),
            'language'       => 'fr',
            'duration_hours' => 10,
        ], $attrs));
        CourseTranslation::create(['course_id' => $course->id, 'locale' => 'fr', 'title' => 'Cours test', 'description' => 'Description']);

        $module = Module::create(['course_id' => $course->id, 'order' => 1, 'title' => 'Module 1', 'duration_hours' => 10]);
        $order = 1;
        foreach ($lessons as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                $lesson = Lesson::create(['module_id' => $module->id, 'order' => $order++, 'type' => $type, 'duration_minutes' => 5]);
                LessonTranslation::create(['lesson_id' => $lesson->id, 'locale' => 'fr', 'title' => ucfirst($type) . ' ' . $order, 'content' => '<p>Contenu</p>']);
                if ($type === 'quiz') {
                    $this->addQuiz($lesson);
                }
            }
        }

        return $course->fresh();
    }

    /** One single-answer question whose correct option is returned. */
    protected function addQuiz(Lesson $lesson, array $attrs = []): QuizOption
    {
        $quiz = Quiz::create(array_merge(['lesson_id' => $lesson->id, 'passing_score' => 70], $attrs));
        $question = QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => '2 + 2 ?', 'type' => 'single', 'order' => 1]);
        $right = QuizOption::create(['question_id' => $question->id, 'option_text' => '4', 'is_correct' => true, 'order' => 1]);
        QuizOption::create(['question_id' => $question->id, 'option_text' => '5', 'is_correct' => false, 'order' => 2]);
        return $right;
    }

    /**
     * Adds $count single-answer questions to a quiz.
     *
     * @return array<int, int> question id => right option id
     */
    protected function addQuestions(Quiz $quiz, int $count, ?int $moduleId = null): array
    {
        $right = [];
        $order = (int) $quiz->questions()->max('order');
        for ($i = 1; $i <= $count; $i++) {
            $question = QuizQuestion::create(['quiz_id' => $quiz->id, 'module_id' => $moduleId, 'question' => "Question $i", 'type' => 'single', 'order' => ++$order]);
            $right[$question->id] = QuizOption::create(['question_id' => $question->id, 'option_text' => 'Bonne', 'is_correct' => true, 'order' => 1])->id;
            QuizOption::create(['question_id' => $question->id, 'option_text' => 'Mauvaise', 'is_correct' => false, 'order' => 2]);
        }
        return $right;
    }

    /**
     * Complete assessment path: a 10-question quiz after every video/text lesson,
     * a 10-question exercise per module and a 20-question final evaluation.
     *
     * @return array{lessons: array<int, array{quiz: Quiz, right: array}>, modules: array<int, array{quiz: Quiz, right: array}>, final: array{quiz: Quiz, right: array}}
     */
    protected function addAssessmentPath(Course $course): array
    {
        $path = ['lessons' => [], 'modules' => []];
        foreach ($course->modules()->with('lessons')->get() as $module) {
            foreach ($module->lessons->whereIn('type', ['video', 'text']) as $lesson) {
                $quiz = Quiz::create(['scope' => Quiz::SCOPE_LESSON, 'lesson_id' => $lesson->id, 'passing_score' => 70]);
                $path['lessons'][$lesson->id] = ['quiz' => $quiz, 'right' => $this->addQuestions($quiz, 10)];
            }
            $exam = Quiz::create(['scope' => Quiz::SCOPE_MODULE, 'module_id' => $module->id, 'passing_score' => 70]);
            $path['modules'][$module->id] = ['quiz' => $exam, 'right' => $this->addQuestions($exam, 10)];
        }
        $final = Quiz::create(['scope' => Quiz::SCOPE_COURSE, 'course_id' => $course->id, 'passing_score' => 70, 'show_correct_answers' => false]);
        $firstModule = $course->modules()->first();
        $path['final'] = ['quiz' => $final, 'right' => $this->addQuestions($final, 20, $firstModule?->id)];

        return $path;
    }

    /** Answers with exactly $correct right answers (the others wrong). */
    protected function answers(array $right, int $correct): array
    {
        $answers = [];
        $i = 0;
        foreach ($right as $questionId => $optionId) {
            $answers[$questionId] = [$i++ < $correct ? $optionId : $optionId + 1];
        }
        return $answers;
    }

    protected function enroll(User $student, Course $course): Enrollment
    {
        return Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now()]);
    }

    protected function lessonsOf(Course $course)
    {
        return $course->modules()->with('lessons')->get()->flatMap->lessons->values();
    }
}
