<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseAnnouncement extends Model
{
    protected $fillable = ['course_id', 'user_id', 'title', 'body'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
