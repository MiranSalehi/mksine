<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Miran\Mksine\Support\Console\AdminConsolePhpBinary;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Locates a Composer executable for CLI core updates.
 *
 * Prefers composer.phar in the project root (same as the admin console),
 * then a `composer` binary on PATH.
 */
final class ComposerBinary
{
    /** @var list<string>|null|false */
    private static array|null|false $fake = false;

    /**
     * @param  list<string>|null  $argv  Null fakes "composer not found".
     */
    public static function fake(?array $argv): void
    {
        self::$fake = $argv;
    }

    public static function clearFake(): void
    {
        self::$fake = false;
    }

    /**
     * @return list<string>|null  Process argv prefix, or null if Composer is unavailable.
     */
    public static function argv(string $projectRoot): ?array
    {
        if (self::$fake !== false) {
            return self::$fake;
        }

        $phar = rtrim($projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'composer.phar';
        if (is_file($phar) && is_readable($phar)) {
            try {
                return [AdminConsolePhpBinary::path(), $phar];
            } catch (\InvalidArgumentException) {
                return ['php', $phar];
            }
        }

        $finder = new ExecutableFinder;
        $found = $finder->find('composer');

        return is_string($found) && $found !== '' ? [$found] : null;
    }

    public static function isAvailable(string $projectRoot): bool
    {
        return self::argv($projectRoot) !== null;
    }
}
