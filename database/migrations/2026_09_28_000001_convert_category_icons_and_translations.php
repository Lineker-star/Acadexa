<?php

use Database\Seeders\CategorySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Categories used emoji as icons: they become SVG icon names (Bootstrap Icons),
 * and the default categories receive their name in all six languages.
 * Existing translations are never overwritten.
 */
return new class extends Migration
{
    /** Emoji previously offered in the admin => SVG icon. */
    private const EMOJI_TO_ICON = [
        '💻' => 'bi-laptop', '🌐' => 'bi-globe2', '📱' => 'bi-phone', '📊' => 'bi-bar-chart-line', '🔐' => 'bi-shield-lock',
        '💼' => 'bi-briefcase', '🚀' => 'bi-rocket-takeoff', '💰' => 'bi-cash-coin', '📋' => 'bi-kanban', '🎨' => 'bi-palette',
        '✏️' => 'bi-vector-pen', '✏' => 'bi-vector-pen', '📸' => 'bi-camera', '🗣️' => 'bi-translate', '🗣' => 'bi-translate',
        '🏥' => 'bi-heart-pulse', '🌱' => 'bi-tree', '📚' => 'bi-book', '🌟' => 'bi-stars', '📁' => 'bi-folder',
        '🎓' => 'bi-mortarboard', '🔬' => 'bi-eyedropper', '⚖️' => 'bi-bank', '🎵' => 'bi-music-note-beamed', '⚽' => 'bi-dribbble',
    ];

    public function up(): void
    {
        $bySlug = [];
        foreach (CategorySeeder::CATEGORIES as $cat) {
            $bySlug[$cat['slug']] = $cat;
            foreach ($cat['children'] ?? [] as $child) {
                $bySlug[$child['slug']] = $child;
            }
        }

        foreach (DB::table('categories')->get() as $category) {
            $icon = $category->icon;
            if (! $icon || ! str_starts_with($icon, 'bi-')) {
                $icon = $bySlug[$category->slug]['icon']
                    ?? self::EMOJI_TO_ICON[trim((string) $icon)]
                    ?? 'bi-folder';
                DB::table('categories')->where('id', $category->id)->update(['icon' => $icon]);
            }

            foreach ($bySlug[$category->slug]['names'] ?? [] as $locale => $name) {
                $exists = DB::table('category_translations')
                    ->where('category_id', $category->id)->where('locale', $locale)->exists();
                if (! $exists) {
                    DB::table('category_translations')->insert([
                        'category_id' => $category->id, 'locale' => $locale, 'name' => $name,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Icon names stay valid; nothing to undo safely.
    }
};
