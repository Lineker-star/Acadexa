<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ACADEXA belongs to the "Institut de Formation Professionnelle ZTF": the certificate shows that
 * name. Only the untouched default values are replaced; anything typed by the admin is kept.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        'cert_institution' => ['ZTF University Institute', 'Institut de Formation Professionnelle ZTF'],
        'cert_sig_title'   => ['Director, ZTF University Institute', 'Directeur, Institut de Formation Professionnelle ZTF'],
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $key => [$old, $new]) {
            $this->replace($key, $old, $new);
        }
    }

    public function down(): void
    {
        foreach (self::DEFAULTS as $key => [$old, $new]) {
            $this->replace($key, $new, $old);
        }
    }

    private function replace(string $key, string $from, string $to): void
    {
        DB::table('settings')->where('key', $key)->where('value', $from)->update(['value' => $to]);
        // Settings are cached for an hour (Setting::get and the views' $siteSettings).
        rescue(function () use ($key) {
            Cache::forget("setting_{$key}");
            Cache::forget('site_settings');
        }, report: false);
    }
};
