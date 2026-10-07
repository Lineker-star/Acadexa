<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Icons;
use Illuminate\Http\Response;

/**
 * Serves the PWA files through Laravel (public/ is not versioned on this project):
 * the web app manifest, the service worker and the icons.
 */
class PwaController extends Controller
{
    private const ICONS = ['icon-192.png', 'icon-512.png', 'icon-maskable-512.png', 'apple-touch-icon.png', 'badge-96.png'];

    public function manifest()
    {
        $name = Setting::get('site_name', 'ACADEXXA');

        return response()->json([
            'id'               => '/',
            'name'             => $name . ' — ZTF University Institute',
            'short_name'       => $name,
            'description'      => __('lms.pwa_description'),
            'lang'             => app()->getLocale(),
            'start_url'        => '/dashboard?source=pwa',
            'scope'            => '/',
            'display'          => 'standalone',
            'orientation'      => 'any',
            'background_color' => '#ffffff',
            'theme_color'      => '#0A2A5E',
            'categories'       => ['education'],
            'icons' => [
                ['src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => __('navigation.my_courses'), 'url' => '/my-courses', 'icons' => [['src' => '/pwa/icon-192.png', 'sizes' => '192x192']]],
                ['name' => __('lms.offline_courses'), 'url' => '/offline', 'icons' => [['src' => '/pwa/icon-192.png', 'sizes' => '192x192']]],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function serviceWorker(): Response
    {
        $assets = $this->buildAssets();
        $version = substr(sha1(implode('|', $assets) . filemtime(resource_path('views/pwa/sw.blade.php'))), 0, 12);

        return response()
            ->view('pwa.sw', ['version' => $version, 'assets' => $assets])
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Service-Worker-Allowed', '/');
    }

    public function icon(string $name)
    {
        abort_unless(in_array($name, self::ICONS, true), 404);

        return response()->file(resource_path('pwa/' . $name), [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /** SVG icon sprite used by <x-icon> and icon() — immutable per version. */
    public function iconSprite()
    {
        abort_unless(is_file(Icons::path()), 404);

        return response()->file(Icons::path(), [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /** App shell precached by the service worker: offline page, icons, CDN styles and every compiled Vite file. */
    private function buildAssets(): array
    {
        $files = [
            '/offline',
            '/manifest.webmanifest',
            Icons::url(),
            '/pwa/icon-192.png',
            '/pwa/icon-512.png',
            '/images/logo.png',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        ];

        $manifest = public_path('build/manifest.json');
        if (! is_file($manifest)) {
            return $files;
        }

        foreach (json_decode(file_get_contents($manifest), true) ?: [] as $entry) {
            $files[] = '/build/' . $entry['file'];
            foreach ($entry['css'] ?? [] as $css) {
                $files[] = '/build/' . $css;
            }
        }
        return array_values(array_unique($files));
    }
}
