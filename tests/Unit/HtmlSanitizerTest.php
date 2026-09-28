<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_keeps_formatting(): void
    {
        $html = '<h2>Titre</h2><p>Du <strong>gras</strong> et <em>italique</em> — é à ç</p><ul><li>un</li></ul>';
        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function test_removes_scripts_and_event_handlers(): void
    {
        $out = HtmlSanitizer::clean('<p onclick="alert(1)">ok</p><script>alert(1)</script><img src=x onerror=alert(1)>');
        $this->assertStringNotContainsString('script', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringContainsString('<p>ok</p>', $out);
    }

    public function test_blocks_javascript_urls_even_obfuscated(): void
    {
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', "java\nscript:alert(1)", '&#106;avascript:alert(1)', 'data:text/html;base64,xx'] as $url) {
            $out = HtmlSanitizer::clean('<a href="' . $url . '">x</a>');
            $this->assertStringNotContainsString('href', $out, $url);
        }
    }

    public function test_keeps_safe_links_and_adds_rel(): void
    {
        $out = HtmlSanitizer::clean('<a href="https://ztfuniversity.com" target="_blank">site</a>');
        $this->assertStringContainsString('href="https://ztfuniversity.com"', $out);
        $this->assertStringContainsString('rel="noopener noreferrer"', $out);
    }

    public function test_only_allows_video_iframes(): void
    {
        $this->assertStringContainsString('iframe', HtmlSanitizer::clean('<iframe src="https://www.youtube.com/embed/abc"></iframe>'));
        $this->assertStringNotContainsString('iframe', HtmlSanitizer::clean('<iframe src="https://evil.example/"></iframe>'));
    }

    public function test_unwraps_unknown_tags_and_strips_style(): void
    {
        $out = HtmlSanitizer::clean('<section style="color:red"><p style="x">texte</p></section>');
        $this->assertSame('<p>texte</p>', $out);
    }
}
