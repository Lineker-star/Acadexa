<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['quiz_id', 'module_id', 'question', 'type', 'explanation', 'order'];

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
