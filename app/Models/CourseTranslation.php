<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseTranslation extends Model
{
    protected $fillable = [
        'course_id', 'locale', 'title', 'description',
        'requirements', 'what_you_learn', 'meta_title', 'meta_description',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
