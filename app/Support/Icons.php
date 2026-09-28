<?php

namespace App\Support;

/**
 * SVG icon sprite (resources/icons/sprite.svg, built by `npm run icons`).
 * Icons are referenced with <x-icon name="..."> in Blade and icon('...') in JavaScript.
 */
class Icons
{
    private static ?string $url = null;

    public static function path(): string
    {
        return resource_path('icons/sprite.svg');
    }

    /** Versioned URL, so browsers and the service worker refresh it when icons change. */
    public static function url(): string
    {
        if (self::$url === null) {
            $version = is_file(self::path()) ? substr(md5_file(self::path()), 0, 10) : '0';
            self::$url = '/icons.svg?v=' . $version;
        }
        return self::$url;
    }

    /**
     * Inner markup of one icon (paths of a 16×16 viewBox), to embed it in a standalone SVG
     * such as a generated course cover — an <img> SVG cannot reference the external sprite.
     */
    public static function symbol(?string $name): ?string
    {
        static $sprite = null;
        $sprite ??= is_file(self::path()) ? file_get_contents(self::path()) : '';
        $id = preg_quote(self::normalize($name), '/');
        return preg_match('/<symbol[^>]*id="' . $id . '"[^>]*>(.*?)<\/symbol>/s', $sprite, $m) ? $m[1] : null;
    }

    /** Accepts "play-circle" or the legacy "bi-play-circle" form stored in data. */
    public static function normalize(?string $name): string
    {
        $name = trim((string) $name);
        // Legacy values may carry extra classes ("bi-check text-success"): keep the icon token.
        foreach (preg_split('/\s+/', $name) as $token) {
            if (str_starts_with($token, 'bi-')) {
                return substr($token, 3);
            }
        }
        return preg_replace('/[^a-z0-9-]/', '', $name) ?: 'circle';
    }
}
