<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected $fillable = ['slug', 'is_active', 'hero_image'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function translations()
    {
        return $this->hasMany(CmsPageTranslation::class);
    }

    public function translation(string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->where('locale', $locale)->first()
            ?? $this->translations->where('locale', 'en')->first();
    }
}
