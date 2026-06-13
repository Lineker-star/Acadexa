<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id', 'category_id', 'thumbnail', 'level', 'price',
        'status', 'featured', 'duration_minutes', 'slug', 'admin_feedback',
    ];

    protected function casts(): array
    {
        return [
            'featured'         => 'boolean',
            'price'            => 'decimal:2',
            'duration_minutes' => 'integer',
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

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function translation(string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $trans = $this->translations->where('locale', $locale)->first();
        if (! $trans) {
            $trans = $this->translations->where('locale', 'en')->first();
        }
        return $trans;
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
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function reviewCount(): int
    {
        return $this->reviews()->count();
    }

    public function enrollmentCount(): int
    {
        return $this->enrollments()->count();
    }

    public function thumbnailUrl(): string
    {
        if ($this->thumbnail) {
            return asset('storage/thumbnails/' . $this->thumbnail);
        }
        return asset('images/course-placeholder.jpg');
    }

    public function durationFormatted(): string
    {
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;
        if ($h > 0) return "{$h}h {$m}m";
        return "{$m}m";
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
