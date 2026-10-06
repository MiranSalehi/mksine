<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

/**
 * Reads top-level scalar fields out of a `plugin.php` manifest **without executing it**.
 *
 * An uploaded archive is untrusted: `require`-ing its manifest to learn the plugin id
 * would hand arbitrary code execution to anyone who can reach the upload form, before
 * a single validation rule has run. This scanner tokenises the source instead, so the
 * upload pipeline can derive an id, reject the archive, and never run plugin code.
 *
 * Only string literals in the outermost `return [...]` array are recognised. Manifests
 * that compute their id (concatenation, constants, function calls) are reported as
 * missing and rejected, which is the safe default.
 */
final class PluginManifestSource
{
    /**
     * Manifests are metadata files; anything larger is not one and is not worth tokenising.
     */
    private const MAX_SOURCE_BYTES = 262144;

    private const FIELDS = ['id', 'name', 'version'];

    /**
     * @return array{id: ?string, name: ?string, version: ?string}
     */
    public static function scan(string $source): array
    {
        /** @var array{id: ?string, name: ?string, version: ?string} $fields */
        $fields = array_fill_keys(self::FIELDS, null);

        if ($source === '' || strlen($source) > self::MAX_SOURCE_BYTES) {
            return $fields;
        }

        $tokens = self::significantTokens($source);

        if ($tokens === null) {
            return $fields;
        }

        $depth = 0;

        /** @var list<bool> $parenIsArray */
        $parenIsArray = [];

        foreach ($tokens as $index => $token) {
            if ($token === '[') {
                $depth++;

                continue;
            }

            if ($token === ']') {
                $depth = max(0, $depth - 1);

                continue;
            }

            if ($token === '(') {
                $previous = $tokens[$index - 1] ?? null;
                $isArray = is_array($previous) && $previous[0] === T_ARRAY;
                $parenIsArray[] = $isArray;

                if ($isArray) {
                    $depth++;
                }

                continue;
            }

            if ($token === ')') {
                if (array_pop($parenIsArray) === true) {
                    $depth = max(0, $depth - 1);
                }

                continue;
            }

            if ($depth !== 1 || ! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $key = self::unquote($token[1]);

            if ($key === null || ! array_key_exists($key, $fields) || $fields[$key] !== null) {
                continue;
            }

            $arrow = $tokens[$index + 1] ?? null;
            $value = $tokens[$index + 2] ?? null;

            if (! is_array($arrow) || $arrow[0] !== T_DOUBLE_ARROW) {
                continue;
            }

            if (! is_array($value) || $value[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $fields[$key] = self::unquote($value[1]);
        }

        return $fields;
    }

    /**
     * Tokenise the manifest, dropping whitespace and comments so callers can rely on
     * adjacency (`key`, `=>`, `value`) and on balanced bracket counting.
     *
     * @return list<string|array{0: int, 1: string, 2: int}>|null
     */
    private static function significantTokens(string $source): ?array
    {
        try {
            $tokens = @token_get_all($source);
        } catch (\Throwable) {
            return null;
        }

        $ignored = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

        return array_values(array_filter(
            $tokens,
            static fn (string|array $token): bool => is_string($token) || ! in_array($token[0], $ignored, true),
        ));
    }

    private static function unquote(string $literal): ?string
    {
        if (strlen($literal) < 2) {
            return null;
        }

        $quote = $literal[0];

        if (($quote !== "'" && $quote !== '"') || $literal[strlen($literal) - 1] !== $quote) {
            return null;
        }

        $inner = substr($literal, 1, -1);

        if ($quote === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $inner);
        }

        return stripcslashes($inner);
    }
}
