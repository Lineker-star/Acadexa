<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'course_id', 'rating', 'comment', 'is_flagged'];

    protected function casts(): array
    {
        return ['is_flagged' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
