<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

use InvalidArgumentException;

/**
 * Parses admin terminal input into a non-shell Process argv array.
 *
 * Only {@code artisan} / {@code php artisan} and {@code composer} prefixes are allowed.
 * Shell metacharacters are rejected so operators cannot chain arbitrary commands.
 */
final class AdminConsoleCommandParser
{
    private const MAX_COMMAND_LENGTH = 2000;

    /**
     * @return array{runner: string, display: string, argv: list<string>}
     */
    public static function parse(string $input, string $projectRoot): array
    {
        $input = trim($input);
        if ($input === '') {
            throw new InvalidArgumentException('Command cannot be empty.');
        }

        if (strlen($input) > self::MAX_COMMAND_LENGTH) {
            throw new InvalidArgumentException('Command is too long.');
        }

        if (preg_match('/[;&|`$<>#\r\n\x00-\x08\x0B\x0C\x0E-\x1F]/', $input) === 1) {
            throw new InvalidArgumentException('Shell operators and control characters are not allowed.');
        }

        $normalized = preg_replace('/\s+/u', ' ', $input) ?? $input;

        if (preg_match('/^(?:php\s+)?artisan\s+(.+)$/iu', $normalized, $matches) === 1) {
            $tail = trim($matches[1]);
            if ($tail === '') {
                throw new InvalidArgumentException('Artisan sub-command is required.');
            }

            $argv = array_merge(
                [AdminConsolePhpBinary::path(), self::artisanPath($projectRoot)],
                self::tokenize($tail),
            );

            return [
                'runner' => 'artisan',
                'display' => $normalized,
                'argv' => $argv,
            ];
        }

        if (preg_match('/^composer\s+(.+)$/iu', $normalized, $matches) === 1) {
            $tail = trim($matches[1]);
            if ($tail === '') {
                throw new InvalidArgumentException('Composer sub-command is required.');
            }

            $argv = array_merge(
                self::composerPrefix($projectRoot),
                self::tokenize($tail),
            );

            return [
                'runner' => 'composer',
                'display' => $normalized,
                'argv' => $argv,
            ];
        }

        throw new InvalidArgumentException('Only "php artisan …" and "composer …" commands are allowed.');
    }

    private static function artisanPath(string $projectRoot): string
    {
        $path = rtrim($projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'artisan';
        if (! is_file($path)) {
            throw new InvalidArgumentException('artisan file was not found in the project root.');
        }

        return $path;
    }

    /**
     * @return list<string>
     */
    private static function composerPrefix(string $projectRoot): array
    {
        $phar = rtrim($projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'composer.phar';
        if (is_file($phar)) {
            return [AdminConsolePhpBinary::path(), $phar];
        }

        return ['composer'];
    }

    /**
     * @return list<string>
     */
    private static function tokenize(string $tail): array
    {
        $tokens = [];
        if (preg_match_all('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|(\S+)/u', $tail, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                if ($match[1] !== '') {
                    $tokens[] = stripcslashes($match[1]);
                } elseif ($match[2] !== '') {
                    $tokens[] = stripcslashes($match[2]);
                } elseif ($match[3] !== '') {
                    $tokens[] = $match[3];
                }
            }
        }

        if ($tokens === []) {
            throw new InvalidArgumentException('Could not parse command arguments.');
        }

        return $tokens;
    }
}
