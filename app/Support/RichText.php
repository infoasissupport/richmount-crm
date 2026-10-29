<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Small, safe rich text for quotation line item descriptions (the builder's B / text colour / background editor).
 * Only bold, italic, underline, line breaks and colour / background-colour styles survive; everything else is dropped.
 */
class RichText
{
    /** Tags kept as-is (attributes stripped); block tags become line breaks. */
    protected const INLINE = ['b', 'strong', 'i', 'em', 'u', 'span'];
    protected const BLOCK = ['div', 'p'];

    public static function isHtml(?string $text): bool
    {
        return $text !== null && preg_match('/<[a-z][^>]*>/i', $text) === 1;
    }

    /**
     * Clean editor HTML down to the allowed tags and colour styles. Plain text is returned unchanged.
     */
    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (! self::isHtml($html)) {
            return $html;
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="rt-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $doc->getElementById('rt-root');
        $out = $root ? self::cleanChildren($root) : '';

        // drop leading/trailing breaks the editor leaves behind
        return preg_replace('/^(<br>)+|(<br>)+$/', '', $out);
    }

    /**
     * HTML to print inside a document cell: cleaned rich text, or escaped plain text with its line breaks.
     */
    public static function toHtml(?string $text): string
    {
        return self::isHtml($text) ? self::clean($text) : nl2br(e((string) $text), false);
    }

    /** Plain text (CSV, integrations, previews). */
    public static function toPlain(?string $text): string
    {
        if (! self::isHtml($text)) {
            return (string) $text;
        }
        $withBreaks = preg_replace('#<br\s*/?>#i', "\n", self::clean($text));

        return trim(html_entity_decode(strip_tags($withBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    protected static function cleanChildren(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= self::cleanNode($child);
        }

        return $out;
    }

    protected static function cleanNode(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return e($node->nodeValue);
        }
        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'template'], true)) {
            return '';
        }
        if ($tag === 'br') {
            return '<br>';
        }

        $inner = self::cleanChildren($node);

        if (in_array($tag, self::BLOCK, true)) {
            // browsers wrap each new line in a <div>: start it on its own line (a leading break is trimmed later)
            return '<br>' . ($inner === '<br>' ? '' : $inner);
        }
        if ($tag === 'font' && $node->hasAttribute('color')) {
            // older browsers' execCommand('foreColor') output
            $color = self::safeColor($node->getAttribute('color'));

            return $color ? '<span style="color: ' . $color . '">' . $inner . '</span>' : $inner;
        }
        if (! in_array($tag, self::INLINE, true)) {
            return $inner;
        }

        $style = self::safeStyle($node->getAttribute('style'));
        if ($tag === 'span') {
            return $style ? '<span style="' . $style . '">' . $inner . '</span>' : $inner;
        }

        return '<' . $tag . ($style ? ' style="' . $style . '"' : '') . '>' . $inner . '</' . $tag . '>';
    }

    /** Keep only color / background-color / font-weight / font-style / text-decoration with safe values. */
    protected static function safeStyle(string $style): string
    {
        $kept = [];
        foreach (explode(';', $style) as $rule) {
            [$prop, $value] = array_pad(array_map('trim', explode(':', $rule, 2)), 2, '');
            $prop = strtolower($prop);
            if (in_array($prop, ['color', 'background-color'], true) && ($c = self::safeColor($value))) {
                $kept[] = $prop . ': ' . $c;
            } elseif ($prop === 'font-weight' && preg_match('/^(bold|[1-9]00)$/i', $value)) {
                $kept[] = 'font-weight: ' . strtolower($value);
            } elseif ($prop === 'font-style' && strtolower($value) === 'italic') {
                $kept[] = 'font-style: italic';
            } elseif ($prop === 'text-decoration' && preg_match('/^underline$/i', $value)) {
                $kept[] = 'text-decoration: underline';
            }
        }

        return implode('; ', $kept);
    }

    /** #rgb / #rrggbb / rgb(...) / rgba(...) only. */
    protected static function safeColor(string $value): ?string
    {
        $value = strtolower(trim($value));
        if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/', $value)) {
            return $value;
        }
        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value)) {
            return preg_replace('/\s+/', '', $value);
        }

        return null;
    }
}
