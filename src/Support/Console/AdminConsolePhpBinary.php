<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

use InvalidArgumentException;
use Symfony\Component\Process\Process;

/**
 * Resolves a PHP CLI binary for admin console subprocesses.
 *
 * Under php-fpm, {@see PHP_BINARY} often points at php-fpm (which cannot run artisan).
 */
final class AdminConsolePhpBinary
{
    private static ?string $resolved = null;

    public static function path(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $configured = config('mksine.console_terminal.php_binary');
        if (is_string($configured) && trim($configured) !== '') {
            self::$resolved = self::assertCliBinary(trim($configured));

            return self::$resolved;
        }

        foreach (self::candidates() as $candidate) {
            if (self::isCliBinary($candidate)) {
                self::$resolved = $candidate;

                return self::$resolved;
            }
        }

        throw new InvalidArgumentException(
            'No PHP CLI binary was found for the admin console. Set MKS_CONSOLE_PHP_BINARY in .env (for example /usr/bin/php8.4).'
        );
    }

    /**
     * @internal
     */
    public static function resetResolved(): void
    {
        self::$resolved = null;
    }

    /**
     * @return list<string>
     */
    private static function candidates(): array
    {
        $candidates = [];

        if (defined('PHP_BINDIR')) {
            $bindir = PHP_BINDIR;
            $candidates[] = $bindir.DIRECTORY_SEPARATOR.'php';
            $candidates[] = $bindir.DIRECTORY_SEPARATOR.'php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
            $candidates[] = $bindir.DIRECTORY_SEPARATOR.'php'.PHP_MAJOR_VERSION;
        }

        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            $binary = PHP_BINARY;

            if (self::looksLikeFpmPath($binary)) {
                $dir = dirname($binary);
                $candidates[] = $dir.DIRECTORY_SEPARATOR.'php';
                $candidates[] = $dir.DIRECTORY_SEPARATOR.'php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
                $candidates[] = $dir.DIRECTORY_SEPARATOR.'php'.PHP_MAJOR_VERSION;

                $replaced = preg_replace('/php-fpm[\d.-]*/i', 'php', $binary);
                if (is_string($replaced) && $replaced !== $binary) {
                    $candidates[] = $replaced;
                }
            } else {
                $candidates[] = $binary;
            }
        }

        $candidates[] = 'php';

        $unique = [];
        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $unique[$candidate] = $candidate;
        }

        return array_values($unique);
    }

    private static function assertCliBinary(string $path): string
    {
        if (! self::isCliBinary($path)) {
            throw new InvalidArgumentException(
                'MKS_CONSOLE_PHP_BINARY must be an executable PHP CLI binary: '.$path
            );
        }

        return self::resolvePath($path);
    }

    private static function isCliBinary(string $path): bool
    {
        if (self::looksLikeFpmPath($path)) {
            return false;
        }

        $resolved = self::resolvePath($path);
        if ($resolved === '' || ! is_file($resolved) || ! is_executable($resolved)) {
            return false;
        }

        $process = new Process([$resolved, '-r', 'echo PHP_SAPI;']);
        $process->setTimeout(10);
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) === 'cli';
    }

    private static function resolvePath(string $path): string
    {
        if ($path === 'php') {
            $which = self::whichPhp();
            if ($which !== null) {
                return $which;
            }
        }

        return $path;
    }

    private static function whichPhp(): ?string
    {
        $process = new Process(['which', 'php']);
        $process->setTimeout(5);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $path = trim($process->getOutput());

        return $path !== '' ? $path : null;
    }

    private static function looksLikeFpmPath(string $path): bool
    {
        return stripos($path, 'fpm') !== false;
    }
}
