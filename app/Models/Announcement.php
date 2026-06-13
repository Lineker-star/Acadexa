<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'audience', 'created_by', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function translations()
    {
        return $this->hasMany(AnnouncementTranslation::class);
    }

    public function translation(string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->where('locale', $locale)->first()
            ?? $this->translations->where('locale', 'en')->first();
    }

    public function titleFor(string $locale = null): string
    {
        return $this->translation($locale)?->title ?? $this->title;
    }

    public function bodyFor(string $locale = null): string
    {
        return $this->translation($locale)?->body ?? $this->body;
    }
}
