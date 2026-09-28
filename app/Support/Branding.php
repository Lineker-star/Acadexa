<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/** Home page photo: the one uploaded in Admin → Settings, otherwise the default photo. */
class Branding
{
    public static function heroUrl(): string
    {
        $custom = Setting::get('hero_image');
        if ($custom && Storage::disk('public')->exists($custom)) {
            return Storage::disk('public')->url($custom);
        }
        return config('lms.hero.default_image');
    }

    /** Credit line, only needed for the default (licensed) photo. */
    public static function heroCredit(): ?string
    {
        $custom = Setting::get('hero_image');
        return $custom && Storage::disk('public')->exists($custom) ? null : config('lms.hero.default_credit');
    }
}
