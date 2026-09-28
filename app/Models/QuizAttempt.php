<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = ['user_id', 'quiz_id', 'mode', 'score', 'correct_count', 'total_questions', 'passed', 'answers', 'module_scores', 'attempted_at'];

    protected function casts(): array
    {
        return [
            'score'        => 'decimal:2',
            'passed'       => 'boolean',
            'answers'      => 'array',
            'module_scores' => 'array',
            'correct_count' => 'integer',
            'total_questions' => 'integer',
            'attempted_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
