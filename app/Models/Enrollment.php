<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'enrolled_at', 'completed_at',
        'progress_percent', 'last_lesson_id', 'reassess_reminded_at', 'inactivity_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at'      => 'datetime',
            'completed_at'     => 'datetime',
            'reassess_reminded_at' => 'datetime',
            'inactivity_reminded_at' => 'datetime',
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
        // Lessons, module exercises and the final evaluation all count (see ProgressService).
        $percent = app(\App\Services\ProgressService::class)->percent($this);
        $completedAt = $percent >= 100 ? ($this->completed_at ?? now()) : null;

        $this->update([
            'progress_percent' => $percent,
            'completed_at'     => $completedAt,
        ]);
    }
}
