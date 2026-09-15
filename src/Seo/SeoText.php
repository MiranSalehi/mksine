<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo;

final class SeoText
{
    public static function normalize(?string $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower(trim($value));
    }

    public static function stripHtml(?string $html): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    public static function containsKeyphrase(string $haystack, string $keyphrase): bool
    {
        $needle = self::normalize($keyphrase);

        if ($needle === '') {
            return false;
        }

        return str_contains(self::normalize($haystack), $needle);
    }

    public static function slugContainsKeyphrase(string $slug, string $keyphrase): bool
    {
        $needle = str_replace(' ', '-', self::normalize($keyphrase));
        $needle = preg_replace('/[^\p{L}\p{N}-]+/u', '', $needle) ?? $needle;

        if ($needle === '') {
            return false;
        }

        $haystack = preg_replace('/[^\p{L}\p{N}-]+/u', '', self::normalize($slug)) ?? self::normalize($slug);

        return str_contains($haystack, $needle);
    }

    public static function wordCount(string $text): int
    {
        $text = trim($text);

        if ($text === '') {
            return 0;
        }

        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? count($parts) : 0;
    }

    public static function characterLength(?string $value): int
    {
        return mb_strlen(trim((string) $value));
    }

    public static function intro(?string $html, int $limit = 300): string
    {
        return mb_substr(self::stripHtml($html), 0, $limit);
    }

    /**
     * @return list<string>
     */
    public static function paragraphs(?string $html): array
    {
        $html = (string) $html;

        if ($html === '') {
            return [];
        }

        if (preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $matches) > 0) {
            $paragraphs = [];

            foreach ($matches[1] as $paragraph) {
                $text = self::stripHtml($paragraph);

                if ($text !== '') {
                    $paragraphs[] = $text;
                }
            }

            if ($paragraphs !== []) {
                return $paragraphs;
            }
        }

        $plain = self::stripHtml($html);

        return $plain === '' ? [] : [$plain];
    }

    /**
     * @return list<string>
     */
    public static function sentences(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $parts = preg_split('/(?<=[\.!\?؟。])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts)) {
            return [$text];
        }

        return array_values(array_filter(array_map('trim', $parts)));
    }

    public static function hasHeading(?string $html): bool
    {
        return preg_match('/<h[1-3]\b/i', (string) $html) === 1;
    }

    /**
     * @return array{internal: int, outbound: int}
     */
    public static function countLinks(?string $html): array
    {
        $internal = 0;
        $outbound = 0;

        if (preg_match_all('/<a\b[^>]*href\s*=\s*["\']([^"\']+)["\']/i', (string) $html, $matches) !== false) {
            foreach ($matches[1] as $href) {
                $href = trim($href);

                if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                    continue;
                }

                if (preg_match('#^(https?:)?//#i', $href) === 1) {
                    $outbound++;
                } else {
                    $internal++;
                }
            }
        }

        return ['internal' => $internal, 'outbound' => $outbound];
    }

    /**
     * English-only Flesch-like score. Not valid for Persian or Kurdish.
     */
    public static function fleschReadingEase(string $text): ?float
    {
        $words = self::wordCount($text);
        $sentences = max(count(self::sentences($text)), 1);

        if ($words === 0) {
            return null;
        }

        $syllables = 0;

        foreach (preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $syllables += self::englishSyllables($word);
        }

        $syllables = max($syllables, $words);

        return 206.835 - (1.015 * ($words / $sentences)) - (84.6 * ($syllables / $words));
    }

    private static function englishSyllables(string $word): int
    {
        $word = strtolower(preg_replace('/[^a-z]/', '', $word) ?? '');

        if ($word === '') {
            return 1;
        }

        $count = preg_match_all('/[aeiouy]+/', $word);

        if (str_ends_with($word, 'e') && $count > 1) {
            $count--;
        }

        return max($count, 1);
    }
}
