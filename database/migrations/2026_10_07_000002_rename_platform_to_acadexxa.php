<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The platform is called ACADEXXA; it was first spelled "ACADEXA". The name is also stored in the
 * database (site name, certificate texts, pages, news, course texts): it is corrected there too.
 *
 * Only the name written as a word is replaced. E-mail addresses, links, file names and the codes
 * of the certificates already issued keep the former spelling: they identify something that exists.
 */
return new class extends Migration
{
    private const NAMES = ['ACADEXA' => 'ACADEXXA', 'Acadexa' => 'Acadexxa'];

    /** Texts shown on the site. */
    private const COLUMNS = [
        'settings'                  => ['value'],
        'cms_page_translations'     => ['title', 'content', 'meta_title', 'meta_description'],
        'announcements'             => ['title', 'body'],
        'announcement_translations' => ['title', 'body'],
        'course_announcements'      => ['title', 'body'],
        'categories'                => ['name'],
        'category_translations'     => ['name'],
        'course_translations'       => ['title', 'description', 'requirements', 'what_you_learn', 'meta_title', 'meta_description'],
        'modules'                   => ['title', 'description'],
        'module_translations'       => ['title', 'description'],
        'lessons'                   => ['content'],
        'lesson_translations'       => ['title', 'content'],
    ];

    public function up(): void
    {
        $this->rename(self::NAMES);
        DB::table('users')->where('name', 'ACADEXA Admin')->update(['name' => 'ACADEXXA Admin']);
    }

    public function down(): void
    {
        $this->rename(array_flip(self::NAMES));
        DB::table('users')->where('name', 'ACADEXXA Admin')->update(['name' => 'ACADEXA Admin']);
    }

    /** @param array<string, string> $names former spelling => new spelling */
    private function rename(array $names): void
    {
        // The name as a word: not inside an address (@, /, .), a file name or a certificate code (NAME-XXXX-…).
        $pattern = '/(?<![\w@\/.\-])(' . implode('|', array_keys($names)) . ')(?![\w@\/]|[.\-]\w)/';

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
            if (! $columns) {
                continue;
            }

            DB::table($table)
                ->where(function ($query) use ($columns, $names) {
                    foreach ($columns as $column) {
                        foreach (array_keys($names) as $name) {
                            $query->orWhere($column, 'like', "%{$name}%");
                        }
                    }
                })
                ->select(['id', ...$columns])
                ->lazyById(100)
                ->each(function (object $row) use ($table, $columns, $pattern, $names) {
                    $changes = [];
                    foreach ($columns as $column) {
                        $text = $row->{$column};
                        $renamed = is_string($text) ? preg_replace_callback($pattern, fn (array $match) => $names[$match[1]], $text) : null;
                        if (is_string($renamed) && $renamed !== $text) {
                            $changes[$column] = $renamed;
                        }
                    }
                    if ($changes) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                });
        }

        // Settings are cached for an hour (Setting::get and the views' $siteSettings).
        rescue(function () {
            foreach (DB::table('settings')->pluck('key') as $key) {
                Cache::forget("setting_{$key}");
            }
            Cache::forget('site_settings');
        }, report: false);
    }
};
