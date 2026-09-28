<?php

namespace App\Support;

/**
 * Measures how complete each language is compared with English:
 * group files (resources/lang/{locale}/*.php) and JSON strings (resources/lang/{locale}.json).
 */
class TranslationCoverage
{
    /** @return array<string, array{total:int, translated:int, missing:array<int,string>}> */
    public static function report(): array
    {
        $reference = self::keys('en');
        $report = [];

        foreach (config('app.supported_locales') as $locale) {
            $keys = self::keys($locale);
            $missing = array_values(array_diff($reference, $keys));
            $report[$locale] = [
                'total'      => count($reference),
                'translated' => count($reference) - count($missing),
                'missing'    => $missing,
            ];
        }

        return $report;
    }

    /** Flattened keys ("group.key.sub") of a locale, plus its JSON strings. */
    public static function keys(string $locale): array
    {
        $keys = [];
        foreach (glob(resource_path("lang/{$locale}/*.php")) ?: [] as $file) {
            $group = basename($file, '.php');
            foreach (self::flatten(include $file) as $key) {
                $keys[] = "{$group}.{$key}";
            }
        }

        // English JSON keys are the source strings themselves, listed in en.json.
        $json = resource_path("lang/{$locale}.json");
        if (is_file($json)) {
            foreach (array_keys(json_decode(file_get_contents($json), true) ?: []) as $key) {
                $keys[] = 'json:' . $key;
            }
        }

        return $keys;
    }

    private static function flatten(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                array_push($keys, ...self::flatten($value, $full));
            } else {
                $keys[] = $full;
            }
        }
        return $keys;
    }
}
