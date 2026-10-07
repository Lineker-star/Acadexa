<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/** Home page image: the photo uploaded in Admin → Settings, otherwise the default ACADEXXA illustration. */
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
}
