<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    use AuthorizesCourse;

    public function updateSettings(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        $data = $request->validate([
            // Platform rule: at least 7/10 to pass. Instructors may only be stricter.
            'passing_score'        => ['required', 'integer', 'min:' . config('lms.assessment.pass_percent'), 'max:100'],
            'max_attempts'         => ['nullable', 'integer', 'min:1', 'max:100'],
            'time_limit_minutes'   => ['nullable', 'integer', 'min:1', 'max:600'],
            'shuffle_questions'    => ['nullable', 'boolean'],
            'show_correct_answers' => ['nullable', 'boolean'],
        ]);

        $quiz->update([
            'passing_score'        => $data['passing_score'],
            'max_attempts'         => $data['max_attempts'] ?? null,
            'time_limit_minutes'   => $data['time_limit_minutes'] ?? null,
            'shuffle_questions'    => $request->boolean('shuffle_questions'),
            'show_correct_answers' => $request->boolean('show_correct_answers'),
        ]);

        return $this->back($quiz, __('lms.quiz_settings_saved'));
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);
        $data = $this->validateQuestion($request, $quiz);

        DB::transaction(function () use ($quiz, $data) {
            $question = QuizQuestion::create([
                'quiz_id'     => $quiz->id,
                'module_id'   => $data['module_id'] ?? null,
                'question'    => $data['question'],
                'type'        => $data['type'],
                'explanation' => $data['explanation'] ?? null,
                'model_answer' => $data['model_answer'] ?? null,
                'order'       => (int) $quiz->questions()->max('order') + 1,
            ]);
            $this->syncOptions($question, $data);
        });

        return $this->back($quiz, __('lms.question_added'));
    }

    public function updateQuestion(Request $request, QuizQuestion $question)
    {
        $quiz = $question->quiz;
        $this->authorizeQuiz($quiz);
        $data = $this->validateQuestion($request, $quiz);

        DB::transaction(function () use ($question, $data) {
            $question->update([
                'question'    => $data['question'],
                'type'        => $data['type'],
                'module_id'   => $data['module_id'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'model_answer' => $data['model_answer'] ?? null,
            ]);
            $question->options()->delete();
            $this->syncOptions($question, $data);
        });

        return $this->back($quiz, __('lms.question_updated'));
    }

    public function destroyQuestion(QuizQuestion $question)
    {
        $quiz = $question->quiz;
        $this->authorizeQuiz($quiz);
        $question->delete();

        return $this->back($quiz, __('lms.question_deleted'));
    }

    public function reorderQuestions(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        abort_if(array_diff($ids, $quiz->questions()->pluck('id')->all()), 422);

        DB::transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                QuizQuestion::whereKey($id)->update(['order' => $position + 1]);
            }
        });

        return response()->json(['message' => __('lms.order_saved')]);
    }

    /**
     * Adds many questions at once from plain text. One block per question, blank line between blocks:
     *   Question text
     *   - wrong option
     *   * right option        (several "*" lines = several right answers)
     *   > explanation shown after answering (optional)
     */
    public function import(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);
        $text = $request->validate(['questions_text' => ['required', 'string', 'max:200000']])['questions_text'];

        if ($quiz->usesOpenQuestions()) {
            return $this->importOpen($quiz, $text);
        }

        [$questions, $errors] = self::parseQuestions($text);
        if ($errors) {
            throw ValidationException::withMessages(['questions_text' => $errors]);
        }

        DB::transaction(function () use ($quiz, $questions) {
            $order = (int) $quiz->questions()->max('order');
            foreach ($questions as $q) {
                $question = QuizQuestion::create([
                    'quiz_id'     => $quiz->id,
                    'question'    => $q['question'],
                    'type'        => count($q['correct']) > 1 ? 'multiple' : 'single',
                    'explanation' => $q['explanation'],
                    'order'       => ++$order,
                ]);
                $this->syncOptions($question, ['options' => $q['options'], 'correct' => $q['correct']]);
            }
        });

        return $this->back($quiz, trans_choice('learn.questions_imported', count($questions), ['count' => count($questions)]));
    }

    /**
     * Module exercise: one block per question, blank line between blocks —
     *   the question on the first line,
     *   then the detailed answer on the following lines (a leading ">" is optional).
     */
    private function importOpen(Quiz $quiz, string $text)
    {
        $blocks = preg_split('/\R\s*\R/u', trim(str_replace("\r\n", "\n", $text)));
        $questions = [];
        $errors = [];
        foreach ($blocks as $n => $block) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $block)), 'strlen'));
            if (! $lines) {
                continue;
            }
            $question = preg_replace('/^(\d+[\.\)]|Q\s*:)\s*/iu', '', array_shift($lines));
            $answer = trim(implode("\n", array_map(fn ($line) => preg_replace('/^>\s*/u', '', $line), $lines)));
            if ($answer === '') {
                $errors[] = __('learn.import_open_error', ['number' => $n + 1, 'question' => \Illuminate\Support\Str::limit($question, 60)]);
                continue;
            }
            $questions[] = ['question' => mb_substr($question, 0, 2000), 'answer' => mb_substr($answer, 0, 20000)];
        }
        if ($errors || ! $questions) {
            throw ValidationException::withMessages(['questions_text' => $errors ?: [__('learn.import_empty')]]);
        }

        DB::transaction(function () use ($quiz, $questions) {
            $order = (int) $quiz->questions()->max('order');
            foreach ($questions as $q) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id, 'question' => $q['question'], 'type' => QuizQuestion::TYPE_OPEN,
                    'model_answer' => $q['answer'], 'order' => ++$order,
                ]);
            }
        });

        return $this->back($quiz, trans_choice('learn.questions_imported', count($questions), ['count' => count($questions)]));
    }

    /** @return array{0: array, 1: string[]} parsed questions and error messages */
    public static function parseQuestions(string $text): array
    {
        $blocks = preg_split('/\R\s*\R/u', trim(str_replace("\r\n", "\n", $text)));
        $questions = [];
        $errors = [];

        foreach ($blocks as $n => $block) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $block)), 'strlen'));
            if (! $lines) {
                continue;
            }
            $question = preg_replace('/^(\d+[\.\)]|Q\s*:)\s*/iu', '', array_shift($lines));
            $options = [];
            $correct = [];
            $explanation = null;
            foreach ($lines as $line) {
                if (preg_match('/^([*+\-])\s*(.+)$/u', $line, $m)) {
                    if ($m[1] !== '-') {
                        $correct[] = count($options);
                    }
                    $options[] = ['text' => mb_substr($m[2], 0, 1000)];
                } elseif (preg_match('/^>\s*(.+)$/u', $line, $m)) {
                    $explanation = mb_substr($m[1], 0, 2000);
                } else {
                    $question .= ' ' . $line;
                }
            }
            if (count($options) < 2 || ! $correct) {
                $errors[] = __('learn.import_block_error', ['number' => $n + 1, 'question' => \Illuminate\Support\Str::limit($question, 60)]);
                continue;
            }
            $questions[] = ['question' => mb_substr($question, 0, 2000), 'options' => $options, 'correct' => $correct, 'explanation' => $explanation];
        }

        if (! $questions && ! $errors) {
            $errors[] = __('learn.import_empty');
        }

        return [$questions, $errors];
    }

    /**
     * Options arrive as options[i][text] + correct[] (indexes of the right answers).
     */
    private function validateQuestion(Request $request, Quiz $quiz): array
    {
        // Module exercise: an open question and the detailed answer shown to students after they reply.
        if ($quiz->usesOpenQuestions()) {
            $data = $request->validate([
                'question'     => ['required', 'string', 'max:2000'],
                'model_answer' => ['required', 'string', 'min:10', 'max:20000'],
            ], [], ['model_answer' => __('learn.model_answer')]);

            return $data + ['type' => QuizQuestion::TYPE_OPEN, 'options' => [], 'correct' => []];
        }

        $data = $request->validate([
            'question'         => ['required', 'string', 'max:2000'],
            'type'             => ['required', 'in:single,multiple'],
            'explanation'      => ['nullable', 'string', 'max:2000'],
            // Final evaluation: the module a question assesses (knowledge per module).
            'module_id'        => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('modules', 'id')->where('course_id', $quiz->ownerCourse()->id)],
            'options'          => ['required', 'array', 'min:2', 'max:10'],
            'options.*.text'   => ['nullable', 'string', 'max:1000'],
            'correct'          => ['required', 'array', 'min:1'],
            'correct.*'        => ['integer', 'min:0'],
        ], [
            'correct.required' => __('lms.quiz_need_correct'),
        ]);

        $filled = collect($data['options'])->filter(fn ($o) => filled($o['text'] ?? null));
        if ($filled->count() < 2) {
            throw ValidationException::withMessages(['options' => __('lms.quiz_need_two_options')]);
        }
        $correct = array_values(array_intersect(array_map('intval', $data['correct']), $filled->keys()->all()));
        if (! $correct) {
            throw ValidationException::withMessages(['correct' => __('lms.quiz_need_correct')]);
        }
        if ($data['type'] === 'single' && count($correct) > 1) {
            throw ValidationException::withMessages(['correct' => __('lms.quiz_single_one_correct')]);
        }

        $data['options'] = $filled->all();
        $data['correct'] = $correct;
        return $data;
    }

    private function syncOptions(QuizQuestion $question, array $data): void
    {
        $order = 1;
        foreach ($data['options'] as $index => $option) {
            QuizOption::create([
                'question_id' => $question->id,
                'option_text' => $option['text'],
                'is_correct'  => in_array((int) $index, $data['correct'], true),
                'order'       => $order++,
            ]);
        }
    }

    private function authorizeQuiz(Quiz $quiz): void
    {
        $course = $quiz->ownerCourse();
        $this->authorizeCourse($course);
        $this->ensureEditable($course);
    }

    private function back(Quiz $quiz, string $message)
    {
        // Invalidates offline copies of the course.
        match ($quiz->scope) {
            Quiz::SCOPE_LESSON => $quiz->lesson->touch(),
            Quiz::SCOPE_MODULE => $quiz->module->touch(),
            default            => $quiz->course->touch(),
        };
        return redirect()->route('instructor.quizzes.edit', $quiz)->with('success', $message);
    }
}
