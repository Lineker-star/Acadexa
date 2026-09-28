<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards the multilingual interface and the SVG icon set:
 *  - every language has every string (and the same :placeholders) as English
 *  - every string used in the code exists in every language
 *  - no text is hard-coded in the Blade views
 *  - no emoji is used as an icon anywhere in the interface
 *  - every icon referenced exists in the SVG sprite
 */
class TranslationCompletenessTest extends TestCase
{
    private function node(): ?string
    {
        $out = [];
        exec('node --version 2>&1', $out, $code);
        return $code === 0 ? 'node' : null;
    }

    public function test_all_languages_are_complete_and_consistent(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(base_path('scripts/check-locales.php')) . ' 2>&1', $out, $code);
        $this->assertSame(0, $code, implode("\n", $out));
    }

    public function test_every_string_used_in_the_code_is_translated(): void
    {
        if (! $node = $this->node()) {
            $this->markTestSkipped('Node.js is required for this check.');
        }
        exec($node . ' ' . escapeshellarg(base_path('scripts/collect-translations.cjs')) . ' --check 2>&1', $out, $code);
        $this->assertSame(0, $code, implode("\n", $out));
    }

    public function test_no_hard_coded_text_in_views(): void
    {
        if (! $node = $this->node()) {
            $this->markTestSkipped('Node.js is required for this check.');
        }
        exec($node . ' ' . escapeshellarg(base_path('scripts/find-hardcoded-text.cjs')) . ' --json 2>&1', $out, $code);
        $found = collect(json_decode(implode("\n", $out), true) ?? [])
            ->reject(fn ($r) => str_starts_with($r['file'], 'resources/views/sitemap'));
        $this->assertCount(0, $found, $found->map(fn ($r) => "{$r['file']}:{$r['line']} {$r['text']}")->implode("\n"));
    }

    public function test_no_emoji_in_the_interface(): void
    {
        $pattern = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F1E6}-\x{1F1FF}\x{FE0F}]/u';
        $problems = [];
        foreach (['resources/views', 'resources/js', 'resources/lang', 'database/seeders', 'config'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                foreach (file($file->getPathname()) as $i => $line) {
                    if (preg_match($pattern, $line)) {
                        $problems[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()) . ':' . ($i + 1);
                    }
                }
            }
        }
        $this->assertSame([], $problems, "Emoji found (use <x-icon>):\n" . implode("\n", $problems));
    }

    public function test_every_referenced_icon_exists_in_the_sprite(): void
    {
        preg_match_all('/<symbol id="([a-z0-9-]+)"/', file_get_contents(resource_path('icons/sprite.svg')), $m);
        $available = array_flip($m[1]);
        $missing = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path(), \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (! preg_match('/\.(php|js)$/', $file->getFilename())) {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            preg_match_all('/<x-icon\b[^>]*?\sname="([a-z0-9-]+)"|\bicon\(\s*\'([a-z0-9-]+)\'/', $src, $refs, PREG_SET_ORDER);
            foreach ($refs as $ref) {
                $name = $ref[1] ?: $ref[2];
                if (! isset($available[$name])) {
                    $missing[] = $file->getFilename() . ': ' . $name;
                }
            }
        }
        $this->assertSame([], array_values(array_unique($missing)), 'Run `npm run icons` to rebuild the sprite.');
    }

    public function test_icon_font_is_no_longer_used(): void
    {
        $offenders = [];
        foreach (['resources/views', 'resources/js'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $src = file_get_contents($file->getPathname());
                if (preg_match('/class="[^"]*\bbi\b|bootstrap-icons/', $src)) {
                    $offenders[] = $file->getFilename();
                }
            }
        }
        $this->assertSame([], $offenders);
    }
}
