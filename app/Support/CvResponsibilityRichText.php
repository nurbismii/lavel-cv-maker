<?php

namespace App\Support;

class CvResponsibilityRichText
{
    private const HTML_KEY = 'html';

    public static function toStorage(?string $html): array
    {
        $cleanHtml = self::sanitize($html);

        return $cleanHtml ? [self::HTML_KEY => $cleanHtml] : [];
    }

    public static function toEditorHtml($value): string
    {
        return self::normalize($value) ?: '';
    }

    public static function toOutputHtml($value): ?string
    {
        return self::normalize($value);
    }

    public static function toPlainText($value): ?string
    {
        $html = self::normalize($value);

        if (!$html) {
            return null;
        }

        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html);
        $text = preg_replace('/<\s*\/\s*(p|li)\s*>/i', "\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = trim(preg_replace('/[ \t\r\n]+/', ' ', $text));

        return $text ?: null;
    }

    public static function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim(str_replace("\0", '', $html));

        if ($html === '') {
            return null;
        }

        $html = preg_replace('/<!--.*?-->/s', '', $html);
        $html = preg_replace('/<\s*(script|style)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html);
        $html = preg_replace('/<\s*div\b[^>]*>/i', '<p>', $html);
        $html = preg_replace('/<\s*\/\s*div\s*>/i', '</p>', $html);
        $html = preg_replace('/<\s*span\b[^>]*>/i', '', $html);
        $html = preg_replace('/<\s*\/\s*span\s*>/i', '', $html);
        $html = preg_replace('/<\s*br\s*\/\s*>/i', '<br>', $html);
        $html = strip_tags($html, '<p><br><ul><ol><li><strong><b><em><i><u>');

        $html = preg_replace_callback('/<\s*(\/?)\s*(p|br|ul|ol|li|strong|b|em|i|u)\b[^>]*>/i', function ($matches) {
            $closing = $matches[1] === '/' ? '/' : '';
            $tag = strtolower($matches[2]);

            if ($tag === 'b') {
                $tag = 'strong';
            }

            if ($tag === 'i') {
                $tag = 'em';
            }

            if ($tag === 'br') {
                return $closing ? '' : '<br>';
            }

            return '<' . $closing . $tag . '>';
        }, $html);

        $html = preg_replace('/<p>\s*(<br>\s*)*<\/p>/i', '', $html);
        $html = preg_replace('/<li>\s*(<br>\s*)*<\/li>/i', '', $html);
        $html = preg_replace('/(<br>\s*){3,}/i', '<br><br>', $html);
        $html = trim($html);

        return self::plainTextFromHtml($html) ? $html : null;
    }

    private static function normalize($value): ?string
    {
        if (is_array($value)) {
            if (isset($value[self::HTML_KEY])) {
                return self::sanitize((string) $value[self::HTML_KEY]);
            }

            return self::legacyListToHtml($value);
        }

        if (is_string($value)) {
            return self::sanitize($value);
        }

        return null;
    }

    private static function legacyListToHtml(array $items): ?string
    {
        $lines = [];

        foreach ($items as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $line = trim(preg_replace('/\s+/', ' ', (string) $item));

            if ($line !== '') {
                $lines[] = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }

        if (!count($lines)) {
            return null;
        }

        return '<ul><li>' . implode('</li><li>', $lines) . '</li></ul>';
    }

    private static function plainTextFromHtml(string $html): ?string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return $text ?: null;
    }
}
