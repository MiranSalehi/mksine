<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Shortcodes;

use Illuminate\Contracts\Container\Container;

final class ShortcodeProcessor
{
    public const FILTER_TAG = 'mksine.shortcode.';

    public function __construct(
        private readonly ShortcodeRegistry $registry,
        private readonly Container $container,
    ) {}

    public function process(string $content, ShortcodeContext $context, ?int $depth = null): string
    {
        $maxDepth = (int) config('mksine.shortcodes.max_depth', 5);
        $depth ??= 0;

        if ($depth >= $maxDepth || $content === '') {
            return $content;
        }

        $maxPasses = (int) config('mksine.shortcodes.max_passes', 2);
        $output = $content;

        for ($pass = 0; $pass < $maxPasses; $pass++) {
            $replaced = $this->processPass($output, $context, $depth);

            if ($replaced === $output) {
                break;
            }

            $output = $replaced;
        }

        return $output;
    }

    public static function stripShortcodes(?string $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        $length = strlen($content);
        $offset = 0;
        $result = '';

        while ($offset < $length) {
            $start = strpos($content, '[', $offset);

            if ($start === false) {
                $result .= substr($content, $offset);

                break;
            }

            $result .= substr($content, $offset, $start - $offset);
            $parsed = self::scanShortcode($content, $start);

            if ($parsed === null) {
                $result .= '[';
                $offset = $start + 1;

                continue;
            }

            [, , , , $end] = $parsed;
            $offset = $end;
        }

        return $result;
    }

    private function processPass(string $content, ShortcodeContext $context, int $depth): string
    {
        $length = strlen($content);
        $offset = 0;
        $result = '';

        while ($offset < $length) {
            $start = strpos($content, '[', $offset);

            if ($start === false) {
                $result .= substr($content, $offset);

                break;
            }

            $result .= substr($content, $offset, $start - $offset);
            $parsed = self::scanShortcode($content, $start);

            if ($parsed === null) {
                $result .= '[';
                $offset = $start + 1;

                continue;
            }

            [$tag, $attrs, $inner, $selfClosing, $end] = $parsed;

            if (! $this->registry->has($tag)) {
                $result .= substr($content, $start, $end - $start);
                $offset = $end;

                continue;
            }

            $processedInner = $inner;

            if (! $selfClosing && $processedInner !== null && $processedInner !== '') {
                $processedInner = $this->process($processedInner, $context, $depth + 1);
            }

            $result .= $this->invokeHandlers($tag, $attrs, $selfClosing ? null : $processedInner, $context);
            $offset = $end;
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: array<string, string>, 2: ?string, 3: bool, 4: int}|null
     */
    private static function scanShortcode(string $content, int $start): ?array
    {
        $length = strlen($content);

        if ($content[$start] !== '[') {
            return null;
        }

        $cursor = $start + 1;

        if ($cursor >= $length || ! ctype_alnum($content[$cursor]) && $content[$cursor] !== '_') {
            return null;
        }

        $tagStart = $cursor;

        while ($cursor < $length && (ctype_alnum($content[$cursor]) || $content[$cursor] === '_')) {
            $cursor++;
        }

        $tag = strtolower(substr($content, $tagStart, $cursor - $tagStart));

        if ($tag === '') {
            return null;
        }

        while ($cursor < $length && ctype_space($content[$cursor])) {
            $cursor++;
        }

        $attrStart = $cursor;

        while ($cursor < $length && $content[$cursor] !== ']' && ! ($content[$cursor] === '/' && ($cursor + 1) < $length && $content[$cursor + 1] === ']')) {
            $cursor++;
        }

        $attrString = trim(substr($content, $attrStart, $cursor - $attrStart));
        $attrs = self::parseAttributes($attrString);

        if ($cursor + 1 < $length && $content[$cursor] === '/' && $content[$cursor + 1] === ']') {
            return [$tag, $attrs, null, true, $cursor + 2];
        }

        if ($cursor >= $length || $content[$cursor] !== ']') {
            return null;
        }

        $cursor++;
        $closeTag = '[/'.$tag.']';
        $closeLen = strlen($closeTag);
        $closePos = strpos($content, $closeTag, $cursor);

        if ($closePos === false) {
            return [$tag, $attrs, null, true, $cursor];
        }

        $innerStart = $cursor;

        while ($cursor < $length) {
            if (substr($content, $cursor, $closeLen) === $closeTag) {
                $inner = substr($content, $innerStart, $cursor - $innerStart);

                return [$tag, $attrs, $inner, false, $cursor + $closeLen];
            }

            if ($content[$cursor] !== '[') {
                $cursor++;

                continue;
            }

            $nested = self::scanShortcode($content, $cursor);

            if ($nested === null) {
                $cursor++;

                continue;
            }

            [, , , , $nestedEnd] = $nested;
            $cursor = $nestedEnd;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private static function parseAttributes(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $attrs = [];
        $pattern = '/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))/';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = strtolower($match[1]);
                $value = $match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : $match[4]);
                $attrs[$name] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $attrs;
    }

    /**
     * @param  array<string, string>  $attrs
     */
    private function invokeHandlers(
        string $tag,
        array $attrs,
        ?string $content,
        ShortcodeContext $context,
    ): string {
        $entries = $this->registry->handlersFor($tag);
        $output = '';

        foreach ($entries as $entry) {
            $output = $this->callHandler($entry['handler'], $attrs, $content, $context);
        }

        $filtered = \Miran\Mksine\Core\Hooks\Hooks::filter(
            self::FILTER_TAG.$tag,
            $output,
            $attrs,
            $content,
            $context,
        );

        return is_string($filtered) ? $filtered : $output;
    }

    private function callHandler(
        callable|string $handler,
        array $attrs,
        ?string $content,
        ShortcodeContext $context,
    ): string {
        if (is_string($handler)) {
            $handler = $this->container->make($handler);
        }

        if ($handler instanceof ShortcodeHandlerInterface) {
            return $handler->handle($attrs, $content, $context);
        }

        if (is_callable($handler)) {
            $result = $handler($attrs, $content, $context);

            return is_string($result) ? $result : '';
        }

        return '';
    }
}
