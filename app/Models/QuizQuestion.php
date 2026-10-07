<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['quiz_id', 'module_id', 'question', 'type', 'explanation', 'model_answer', 'order'];

    public const TYPE_OPEN = 'open';

    /** Open question: the student writes an answer, then reads the instructor's detailed answer. */
    public function isOpen(): bool
    {
        return $this->type === self::TYPE_OPEN;
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options()
    {
        return $this->hasMany(QuizOption::class, 'question_id')->orderBy('order');
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function translations()
    {
        return $this->hasMany(QuizQuestionTranslation::class);
    }
}
