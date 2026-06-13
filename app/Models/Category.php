<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'icon', 'parent_id', 'order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function name(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $trans = $this->translations->where('locale', $locale)->first()
            ?? $this->translations->where('locale', 'en')->first();
        return $trans?->name ?? $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
