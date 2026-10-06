<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Rejects SVG documents that can execute script when served same-origin.
 *
 * This deliberately **rejects** rather than sanitises. A rewriting sanitiser has to be
 * right about every construct a browser might execute; a validator only has to be right
 * about the ones it refuses, and a false rejection costs an upload while a false
 * acceptance costs stored XSS across the whole site.
 *
 * Parsing is done with libxml so entity-encoded payloads are decoded before inspection —
 * matching `<script` against raw bytes misses `&#x3c;script` and every other encoding.
 */
final class SvgSafety
{
    /**
     * Screenshots and icons are small; anything larger is not worth building a DOM for.
     */
    public const MAX_BYTES = 2097152;

    /**
     * Elements that can run script, load a nested browsing context, or retarget an
     * attribute to a script URI after load.
     */
    private const FORBIDDEN_ELEMENTS = [
        'animate',
        'animatemotion',
        'animatetransform',
        'audio',
        'embed',
        'foreignobject',
        'handler',
        'iframe',
        'object',
        'script',
        'set',
        'video',
    ];

    private const FORBIDDEN_URI_SCHEMES = [
        'javascript:',
        'vbscript:',
        'livescript:',
        'mocha:',
        'data:text/html',
        'data:application/xml',
        'data:image/svg+xml',
    ];

    public static function isSvgMime(?string $mime): bool
    {
        if ($mime === null) {
            return false;
        }

        return in_array(strtolower(trim(explode(';', $mime)[0])), [
            'image/svg+xml',
            'image/svg',
        ], true);
    }

    /**
     * Whether an upload has to clear {@see self::isSafe()} before it is stored.
     *
     * The filename matters as much as the reported type: uploads keep their extension on
     * disk, and the web server picks the `Content-Type` from that extension, not from
     * whatever the detector said at upload time.
     */
    public static function isSvgUpload(?string $mime, ?string $filename): bool
    {
        if (self::isSvgMime($mime)) {
            return true;
        }

        return $filename !== null
            && strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'svg';
    }

    public static function fileIsSafe(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $contents = @file_get_contents($path);

        return is_string($contents) && self::isSafe($contents);
    }

    public static function isSafe(string $contents): bool
    {
        if (trim($contents) === '' || strlen($contents) > self::MAX_BYTES) {
            return false;
        }

        // Without ext-dom there is no way to inspect the document; fail closed.
        if (! class_exists(DOMDocument::class)) {
            return false;
        }

        $document = self::parse($contents);

        if ($document === null) {
            return false;
        }

        // An internal DTD subset is how entity expansion and XXE payloads are delivered.
        if ($document->doctype?->internalSubset !== null) {
            return false;
        }

        $root = $document->documentElement;

        if (! $root instanceof DOMElement || strtolower($root->localName) !== 'svg') {
            return false;
        }

        foreach (self::elements($document) as $element) {
            if (! self::elementIsSafe($element)) {
                return false;
            }
        }

        return true;
    }

    private static function parse(string $contents): ?DOMDocument
    {
        $previousErrors = libxml_use_internal_errors(true);

        $document = new DOMDocument;
        // LIBXML_NOENT is deliberately absent: entities must stay unexpanded.
        $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        return $loaded ? $document : null;
    }

    /**
     * @return iterable<DOMElement>
     */
    private static function elements(DOMDocument $document): iterable
    {
        foreach ((new DOMXPath($document))->query('//*') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                yield $node;
            }
        }
    }

    private static function elementIsSafe(DOMElement $element): bool
    {
        $name = strtolower($element->localName);

        if (in_array($name, self::FORBIDDEN_ELEMENTS, true)) {
            return false;
        }

        if ($name === 'style' && ! self::styleTextIsSafe($element->textContent)) {
            return false;
        }

        foreach ($element->attributes ?? [] as $attribute) {
            if (! $attribute instanceof DOMAttr || ! self::attributeIsSafe($name, $attribute)) {
                return false;
            }
        }

        return true;
    }

    private static function attributeIsSafe(string $elementName, DOMAttr $attribute): bool
    {
        $attributeName = strtolower($attribute->localName);

        // Every scripting hook in SVG is an `on*` attribute.
        if (str_starts_with($attributeName, 'on')) {
            return false;
        }

        $value = self::normalize($attribute->value);

        foreach (self::FORBIDDEN_URI_SCHEMES as $scheme) {
            if (str_contains($value, $scheme)) {
                return false;
            }
        }

        if ($attributeName === 'style' && ! self::styleTextIsSafe($attribute->value)) {
            return false;
        }

        // `<use>` pulls a subtree in; only same-document fragments are predictable.
        // `localName` is `href` for both the plain and the `xlink:` prefixed form.
        if ($elementName === 'use' && $attributeName === 'href') {
            return str_starts_with($value, '#');
        }

        return true;
    }

    private static function styleTextIsSafe(string $css): bool
    {
        $normalized = self::normalize($css);

        return ! str_contains($normalized, '@import')
            && ! str_contains($normalized, 'expression(')
            && ! str_contains($normalized, '-moz-binding');
    }

    /**
     * Collapse the whitespace and control characters browsers ignore when resolving a URI,
     * so `java\nscript:alert(1)` cannot slip past a substring check.
     */
    private static function normalize(string $value): string
    {
        return strtolower(preg_replace('/[\s\x00-\x20\x7F]+/u', '', $value) ?? $value);
    }
}
