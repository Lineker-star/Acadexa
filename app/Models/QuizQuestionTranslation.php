<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestionTranslation extends Model
{
    protected $fillable = ['quiz_question_id', 'locale', 'question'];

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
