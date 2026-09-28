<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'lesson_id', 'user_id', 'content', 'file_path', 'original_name',
        'status', 'score', 'feedback', 'graded_by', 'submitted_at', 'graded_at',
    ];

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return [
            'score'        => 'decimal:2',
            'submitted_at' => 'datetime',
            'graded_at'    => 'datetime',
        ];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    public function isPassed(): bool
    {
        return $this->isGraded() && (float) $this->score >= (int) $this->lesson->assignment_pass_score;
    }
}
