<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

/**
 * Validation for plugin and theme identifiers.
 *
 * Identifiers become directory names directly under the plugins/themes roots, so
 * anything containing a separator, a traversal segment, a leading dot or a NUL byte
 * would let an uploaded archive escape its install root.
 */
final class PackageIdentifier
{
    public const PATTERN = '/^[a-z0-9][a-z0-9_-]{0,63}$/';

    public static function isValid(mixed $identifier): bool
    {
        return is_string($identifier) && preg_match(self::PATTERN, $identifier) === 1;
    }

    /**
     * Whether a value is safe to interpolate as a single path segment.
     *
     * Looser than {@see isValid()} on purpose: identifiers already on disk may predate
     * the strict pattern (mixed case, dots), and rejecting them would break live sites.
     * This only guarantees the value cannot traverse or escape its parent directory.
     */
    public static function isSafeSegment(mixed $identifier): bool
    {
        if (! is_string($identifier) || $identifier === '' || strlen($identifier) > 255) {
            return false;
        }

        if (str_contains($identifier, "\0") || str_starts_with($identifier, '.')) {
            return false;
        }

        return preg_match('#[/\\\\]#', $identifier) !== 1;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function assertSafeSegment(mixed $identifier): string
    {
        if (! self::isSafeSegment($identifier)) {
            throw new \InvalidArgumentException('Unsafe package identifier.');
        }

        /** @var string $identifier */
        return $identifier;
    }

    /**
     * Derive an identifier from a human-readable package name.
     *
     * Returns null when nothing usable survives normalisation; callers must treat
     * that as a rejected package rather than falling back to the raw name.
     */
    public static function fromName(string $name): ?string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name))) ?? '';
        $slug = trim(substr(trim($slug, '-'), 0, 64), '-');

        return self::isValid($slug) ? $slug : null;
    }
}
