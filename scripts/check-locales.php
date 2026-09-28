<?php

/**
 * Compares every language with English:
 *  - same keys in each group file (resources/lang/{locale}/*.php) and in {locale}.json
 *  - every :placeholder of the English text is present in the translation
 * Usage: php scripts/check-locales.php   (exit code 1 when something is wrong)
 */

$base = dirname(__DIR__) . '/resources/lang';
$locales = ['fr', 'es', 'pt', 'zh', 'ar'];
$problems = 0;

$flatten = function (array $a, string $prefix = '') use (&$flatten): array {
    $out = [];
    foreach ($a as $k => $v) {
        $key = $prefix . $k;
        if (is_array($v) && ! array_is_list($v)) {
            $out += $flatten($v, $key . '.');
        } else {
            $out[$key] = $v;
        }
    }
    return $out;
};

$placeholders = function ($text): array {
    if (! is_string($text)) {
        return [];
    }
    preg_match_all('/:([a-zA-Z_]+)/', $text, $m);
    $names = array_unique($m[1]);
    sort($names);
    return $names;
};

$report = function (string $where, string $message) use (&$problems) {
    $problems++;
    if ($problems <= 200) {
        echo "[$where] $message\n";
    }
};

$english = [];
foreach (glob("$base/en/*.php") as $file) {
    $english[basename($file, '.php')] = $flatten(require $file);
}
$englishJson = json_decode(file_get_contents("$base/en.json"), true);

foreach ($locales as $locale) {
    foreach ($english as $group => $keys) {
        $file = "$base/$locale/$group.php";
        if (! is_file($file)) {
            $report("$locale/$group", 'file missing');
            continue;
        }
        $translated = $flatten(require $file);
        foreach ($keys as $key => $text) {
            if (! array_key_exists($key, $translated)) {
                $report("$locale/$group", "missing key $key");
                continue;
            }
            $missing = array_diff($placeholders($text), $placeholders($translated[$key]));
            if ($missing) {
                $report("$locale/$group", "$key lacks :" . implode(', :', $missing));
            }
        }
        foreach (array_diff_key($translated, $keys) as $key => $_) {
            $report("$locale/$group", "unknown key $key");
        }
    }

    $jsonFile = "$base/$locale.json";
    $json = is_file($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : null;
    if (! is_array($json)) {
        $report("$locale.json", 'missing or invalid JSON');
        continue;
    }
    foreach ($englishJson as $key => $_) {
        if (! array_key_exists($key, $json)) {
            $report("$locale.json", 'missing ' . json_encode($key, JSON_UNESCAPED_UNICODE));
            continue;
        }
        $missing = array_diff($placeholders($key), $placeholders($json[$key]));
        if ($missing) {
            $report("$locale.json", json_encode($key, JSON_UNESCAPED_UNICODE) . ' lacks :' . implode(', :', $missing));
        }
    }
    foreach (array_diff_key($json, $englishJson) as $key => $_) {
        $report("$locale.json", 'unknown ' . json_encode($key, JSON_UNESCAPED_UNICODE));
    }
}

echo $problems ? "\n$problems problem(s).\n" : "All languages are complete and consistent.\n";
exit($problems ? 1 : 0);
