<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['course_id', 'order', 'title'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function translations()
    {
        return $this->hasMany(ModuleTranslation::class);
    }

    public function title(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $trans = $this->translations->where('locale', $locale)->first()
            ?? $this->translations->where('locale', 'en')->first();
        return $trans?->title ?? $this->getRawOriginal('title') ?? 'Module';
    }
}
