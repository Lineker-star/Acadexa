<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'enrolled_at', 'completed_at',
        'progress_percent', 'last_lesson_id',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at'      => 'datetime',
            'completed_at'     => 'datetime',
            'progress_percent' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function lastLesson()
    {
        return $this->belongsTo(Lesson::class, 'last_lesson_id');
    }

    public function isCompleted(): bool
    {
        return $this->progress_percent >= 100;
    }

    public function recalculateProgress(): void
    {
        $course       = $this->course()->with(['modules.lessons'])->first();
        $totalLessons = $course->modules->sum(fn($m) => $m->lessons->count());

        if ($totalLessons === 0) {
            $this->update(['progress_percent' => 0]);
            return;
        }

        $completed = $this->lessonProgress()->count();
        $percent   = round(($completed / $totalLessons) * 100, 2);
        $completedAt = $percent >= 100 ? now() : null;

        $this->update([
            'progress_percent' => $percent,
            'completed_at'     => $completedAt,
        ]);
    }
}
