<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist HTML sanitizer for rich text written by instructors and admins
 * (lesson content, CMS pages). Removes scripts, event handlers, styles and
 * javascript: URLs; keeps common formatting, links, images, tables and
 * YouTube/Vimeo iframes.
 */
class HtmlSanitizer
{
    private const TAGS = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'sub' => [], 'sup' => [], 'mark' => [], 'small' => [], 'span' => ['class'], 'div' => ['class'],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'ul' => [], 'ol' => ['start'], 'li' => ['class'], 'blockquote' => [], 'pre' => ['class'], 'code' => [],
        'a' => ['href', 'title', 'target'], 'img' => ['src', 'alt', 'title', 'width', 'height'],
        'table' => ['class'], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'figure' => [], 'figcaption' => [],
        'iframe' => ['src', 'width', 'height', 'allowfullscreen', 'frameborder', 'allow'],
    ];

    /** Elements removed together with their content. */
    private const DROP_WITH_CONTENT = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'template', 'noscript'];

    private const IFRAME_HOSTS = '~^https://(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com)/~i';

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return e(strip_tags($html));
        }

        self::cleanChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }

    /** Plain text content suited to notifications and previews. */
    public static function toText(?string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</li>'], ["\n", "\n", "\n"], (string) $html)), ENT_QUOTES, 'UTF-8')));
    }

    private static function cleanChildren(DOMNode $node): void
    {
        // Iterate over a static copy: the live NodeList changes as we edit.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            if (! array_key_exists($tag, self::TAGS)) {
                // Unknown tag: keep its (cleaned) children, drop the wrapper.
                self::cleanChildren($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            self::cleanAttributes($child, self::TAGS[$tag]);

            if ($tag === 'iframe' && ! preg_match(self::IFRAME_HOSTS, $child->getAttribute('src'))) {
                $node->removeChild($child);
                continue;
            }

            self::cleanChildren($child);
        }
    }

    private static function cleanAttributes(DOMElement $el, array $allowed): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }
            if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($attr->nodeValue, $name === 'src')) {
                $el->removeAttribute($attr->nodeName);
            }
        }

        if (strtolower($el->nodeName) === 'a') {
            if ($el->getAttribute('target') === '_blank') {
                $el->setAttribute('rel', 'noopener noreferrer');
            } else {
                $el->removeAttribute('target');
            }
        }
        if (strtolower($el->nodeName) === 'img') {
            $el->setAttribute('loading', 'lazy');
        }
    }

    private static function isSafeUrl(string $url, bool $isSource): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
        // Strip control characters/whitespace browsers ignore inside schemes ("java\nscript:").
        $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', $url));

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')
            || str_starts_with($normalized, '/') || str_starts_with($normalized, '#')) {
            return true;
        }
        if (! $isSource && str_starts_with($normalized, 'mailto:')) {
            return true;
        }
        // Inline images pasted into the editor.
        if ($isSource && preg_match('~^data:image/(png|jpe?g|gif|webp);base64,~', $normalized)) {
            return true;
        }
        // Relative URLs without a scheme.
        return ! preg_match('~^[a-z][a-z0-9+.-]*:~', $normalized);
    }
}
