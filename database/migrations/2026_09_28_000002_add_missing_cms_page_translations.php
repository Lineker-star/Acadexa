<?php

use Database\Seeders\CmsPageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The system pages (about, privacy, terms) receive the languages they are missing.
 * Translations already written by the admin are never replaced.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (CmsPageSeeder::PAGES as $slug => $translations) {
            $page = DB::table('cms_pages')->where('slug', $slug)->first();
            if (! $page) {
                continue;
            }
            foreach ($translations as $locale => $t) {
                $exists = DB::table('cms_page_translations')->where('cms_page_id', $page->id)->where('locale', $locale)->exists();
                if (! $exists) {
                    DB::table('cms_page_translations')->insert([
                        'cms_page_id' => $page->id, 'locale' => $locale, 'title' => $t['title'], 'content' => $t['content'],
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Added content is harmless; nothing to undo.
    }
};
