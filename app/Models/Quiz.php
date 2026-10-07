<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A quiz belongs to exactly one level of the course:
 *  - lesson : the check after a lesson (or the content of a "quiz" lesson),
 *  - module : the exercise that closes a module,
 *  - course : the final evaluation, also used as placement test and re-evaluation.
 */
class Quiz extends Model
{
    public const SCOPE_LESSON = 'lesson';
    public const SCOPE_MODULE = 'module';
    public const SCOPE_COURSE = 'course';

    /** Attempt modes: a diagnostic or a retake never changes the course progress. */
    public const MODE_STANDARD   = 'standard';
    public const MODE_DIAGNOSTIC = 'diagnostic';
    public const MODE_RETAKE     = 'retake';

    protected $fillable = [
        'scope', 'lesson_id', 'module_id', 'course_id', 'passing_score', 'max_attempts',
        'time_limit_minutes', 'shuffle_questions', 'show_correct_answers',
    ];

    protected $attributes = ['scope' => self::SCOPE_LESSON];

    /** Question count preloaded for a whole course (see ProgressService::loadPath). */
    public ?int $preloadedQuestionCount = null;

    protected function casts(): array
    {
        return [
            'passing_score'        => 'integer',
            'max_attempts'         => 'integer',
            'time_limit_minutes'   => 'integer',
            'shuffle_questions'    => 'boolean',
            'show_correct_answers' => 'boolean',
        ];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /** The course this quiz is part of, whatever its level. */
    public function ownerCourse(): Course
    {
        return match ($this->scope) {
            self::SCOPE_COURSE => $this->course,
            self::SCOPE_MODULE => $this->module->course,
            default            => $this->lesson->module->course,
        };
    }

    public function title(?string $locale = null): string
    {
        return match ($this->scope) {
            self::SCOPE_COURSE => __('learn.final_evaluation'),
            self::SCOPE_MODULE => __('learn.module_exercise_of', ['module' => $this->module?->title($locale)]),
            default            => __('learn.lesson_quiz_of', ['lesson' => $this->lesson?->title($locale)]),
        };
    }

    public function icon(): string
    {
        return match ($this->scope) {
            self::SCOPE_COURSE => 'bi-trophy',
            self::SCOPE_MODULE => 'bi-clipboard-check',
            default            => 'bi-patch-question',
        };
    }

    public function minQuestions(): int
    {
        return (int) config("lms.assessment.min_questions.{$this->scope}", 10);
    }

    public function questionCount(): int
    {
        if (array_key_exists('questions_count', $this->attributes)) {
            return (int) $this->attributes['questions_count'];
        }
        if ($this->preloadedQuestionCount !== null && ! $this->relationLoaded('questions')) {
            return $this->preloadedQuestionCount;
        }
        return $this->relationLoaded('questions') ? $this->questions->count() : $this->questions()->count();
    }

    /** Enough questions to be part of the student's path. */
    public function isReady(): bool
    {
        return $this->questionCount() >= $this->minQuestions();
    }

    /** Never below the platform rule (7/10). */
    public function effectivePassingScore(): int
    {
        return max((int) $this->passing_score, (int) config('lms.assessment.pass_percent'));
    }

    /** Module exercises are made of open questions with a detailed answer (no multiple choice). */
    public function usesOpenQuestions(): bool
    {
        return $this->scope === self::SCOPE_MODULE;
    }

    /** What the grade is expressed on: 10 for a lesson quiz (1 point per question), 100 for the final evaluation. */
    public function gradeOutOf(): ?int
    {
        return match ($this->scope) {
            self::SCOPE_LESSON => 10,
            self::SCOPE_COURSE => 100,
            default            => null,
        };
    }

    public function isFinal(): bool
    {
        return $this->scope === self::SCOPE_COURSE;
    }

    /** Attempts that count for the course path (diagnostics and retakes do not). */
    public function standardAttempts()
    {
        return $this->attempts()->where('mode', self::MODE_STANDARD);
    }

    public function userAttempt(int $userId)
    {
        return $this->standardAttempts()->where('user_id', $userId)->latest('id')->first();
    }

    public function attemptsCount(int $userId): int
    {
        return $this->standardAttempts()->where('user_id', $userId)->count();
    }

    public function hasPassed(int $userId): bool
    {
        return $this->standardAttempts()->where('user_id', $userId)->where('passed', true)->exists();
    }

    /** Remaining attempts, or null when unlimited. */
    public function attemptsLeft(int $userId): ?int
    {
        if (! $this->max_attempts) {
            return null;
        }
        return max(0, $this->max_attempts - $this->attemptsCount($userId));
    }
}
