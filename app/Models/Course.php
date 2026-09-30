<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'instructor_id', 'category_id', 'thumbnail', 'level', 'price',
        'status', 'featured', 'duration_minutes', 'duration_hours', 'language',
        'is_sequential', 'slug', 'admin_feedback', 'published_at', 'intro_youtube_id',
        'intro_video_path', 'intro_video_mime', 'intro_video_size',
    ];

    protected $hidden = ['intro_video_path'];

    protected function casts(): array
    {
        return [
            'featured'         => 'boolean',
            'is_sequential'    => 'boolean',
            'price'            => 'decimal:2',
            'duration_minutes' => 'integer',
            'duration_hours'   => 'decimal:1',
            'published_at'     => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function translations()
    {
        return $this->hasMany(CourseTranslation::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    /** Final evaluation: closes the course and measures knowledge over time. */
    public function finalExam()
    {
        return $this->hasOne(Quiz::class)->where('scope', Quiz::SCOPE_COURSE);
    }

    public function hasIntroVideo(): bool
    {
        return (bool) ($this->intro_youtube_id || $this->intro_video_path);
    }

    public function books()
    {
        return $this->hasMany(Book::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['progress_percent', 'enrolled_at', 'completed_at']);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function wishlisted()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function announcements()
    {
        return $this->hasMany(CourseAnnouncement::class)->latest();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function translation(string $locale = null)
    {
        return $this->translationFor($locale);
    }

    public function title(string $locale = null): string
    {
        return $this->translation($locale)?->title ?? 'Untitled Course';
    }

    public function description(string $locale = null): string
    {
        return $this->translation($locale)?->description ?? '';
    }

    public function avgRating(): float
    {
        // Use the preloaded withAvg() value or loaded relation when available to avoid N+1 queries.
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            return round((float) $this->attributes['reviews_avg_rating'], 1);
        }
        if ($this->relationLoaded('reviews')) {
            return round($this->reviews->avg('rating') ?? 0, 1);
        }
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function reviewCount(): int
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }
        return $this->relationLoaded('reviews') ? $this->reviews->count() : $this->reviews()->count();
    }

    public function enrollmentCount(): int
    {
        if (array_key_exists('enrollments_count', $this->attributes)) {
            return (int) $this->attributes['enrollments_count'];
        }
        return $this->relationLoaded('enrollments') ? $this->enrollments->count() : $this->enrollments()->count();
    }

    public function thumbnailUrl(): string
    {
        if ($this->thumbnail) {
            if (str_starts_with($this->thumbnail, 'http')) {
                return $this->thumbnail;
            }
            return asset('storage/thumbnails/' . $this->thumbnail);
        }
        // No uploaded picture: a cover generated from the title and the category.
        return route('courses.cover', ['course' => $this->id, 'v' => $this->updated_at?->timestamp]);
    }

    /** Video/reading time computed from lesson durations. */
    public function durationFormatted(): string
    {
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;
        if ($h > 0) {
            return $m > 0 ? __('lms.hours_minutes_short', ['hours' => $h, 'minutes' => $m]) : __('lms.hours_short', ['count' => $h]);
        }
        return __('lms.minutes_short', ['count' => $m]);
    }

    /** Declared teaching volume (volume horaire), falling back to lesson time. */
    public function hoursLabel(): string
    {
        if ($this->duration_hours !== null && (float) $this->duration_hours > 0) {
            return self::formatHours((float) $this->duration_hours);
        }
        return $this->durationFormatted();
    }

    /** Sum of the hours declared on the modules. */
    public function modulesHoursTotal(): float
    {
        $modules = $this->relationLoaded('modules') ? $this->modules : $this->modules()->get();
        return (float) $modules->sum(fn ($m) => (float) $m->duration_hours);
    }

    public function recalculateDuration(): void
    {
        $this->update(['duration_minutes' => (int) $this->lessons()->sum('lessons.duration_minutes')]);
    }

    public static function formatHours(float $hours): string
    {
        $whole = (int) floor($hours);
        $minutes = (int) round(($hours - $whole) * 60);
        return $minutes > 0
            ? __('lms.hours_minutes_short', ['hours' => $whole, 'minutes' => $minutes])
            : __('lms.hours_short', ['count' => $whole]);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
