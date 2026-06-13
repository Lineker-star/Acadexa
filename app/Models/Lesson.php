<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'module_id', 'order', 'type', 'video_url',
        'content', 'attachment_path', 'duration_minutes', 'is_free_preview',
    ];

    protected function casts(): array
    {
        return [
            'is_free_preview'  => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function translations()
    {
        return $this->hasMany(LessonTranslation::class);
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }

    public function comments()
    {
        return $this->hasMany(LessonComment::class)->whereNull('parent_id')->with('replies.user')->latest();
    }

    public function title(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $trans = $this->translations->where('locale', $locale)->first()
            ?? $this->translations->where('locale', 'en')->first();
        return $trans?->title ?? 'Lesson';
    }
}
