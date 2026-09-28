<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonWatchState extends Model
{
    protected $fillable = ['user_id', 'lesson_id', 'position_seconds', 'max_watched_seconds', 'duration_seconds'];

    protected function casts(): array
    {
        return [
            'position_seconds'    => 'integer',
            'max_watched_seconds' => 'integer',
            'duration_seconds'    => 'integer',
        ];
    }

    public function watchedRatio(): float
    {
        return $this->duration_seconds > 0 ? min(1, $this->max_watched_seconds / $this->duration_seconds) : 0;
    }
}
