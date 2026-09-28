<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasTranslations;

    protected $fillable = ['course_id', 'order', 'title', 'duration_hours', 'description'];

    protected function casts(): array
    {
        return ['duration_hours' => 'decimal:1'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /** Exercise that closes the module. */
    public function exam()
    {
        return $this->hasOne(Quiz::class)->where('scope', Quiz::SCOPE_MODULE);
    }

    public function translations()
    {
        return $this->hasMany(ModuleTranslation::class);
    }

    public function title(string $locale = null): string
    {
        return $this->translationFor($locale)?->title ?? $this->getRawOriginal('title') ?? 'Module';
    }

    public function descriptionText(string $locale = null): string
    {
        return $this->translationFor($locale)?->description ?? (string) $this->getRawOriginal('description');
    }

    public function hoursLabel(): ?string
    {
        if ($this->duration_hours === null || (float) $this->duration_hours <= 0) {
            return null;
        }
        return Course::formatHours((float) $this->duration_hours);
    }

    /** Sum of lesson minutes in this module. */
    public function lessonsMinutes(): int
    {
        $lessons = $this->relationLoaded('lessons') ? $this->lessons : $this->lessons()->get();
        return (int) $lessons->sum('duration_minutes');
    }
}
