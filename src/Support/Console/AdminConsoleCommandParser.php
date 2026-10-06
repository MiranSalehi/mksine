<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

use InvalidArgumentException;

/**
 * Parses admin terminal input into a non-shell Process argv array.
 *
 * Only {@code artisan} / {@code php artisan} and {@code composer} prefixes are allowed.
 * Shell metacharacters are rejected so operators cannot chain arbitrary commands.
 *
 * The prefix check alone is not a boundary: `artisan tinker --execute '…'` evaluates
 * arbitrary PHP, which turns a stolen Super Admin session into a shell. Sub-commands are
 * therefore matched against an allowlist (`mksine.console_terminal.allowed_commands`);
 * set a runner's list to `['*']` to restore the unrestricted behaviour.
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

            $tokens = self::tokenize($tail);
            self::assertAllowed('artisan', $tokens);

            $argv = array_merge(
                [AdminConsolePhpBinary::path(), self::artisanPath($projectRoot)],
                $tokens,
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

            $tokens = self::tokenize($tail);
            self::assertAllowed('composer', $tokens);

            $argv = array_merge(
                self::composerPrefix($projectRoot),
                $tokens,
            );

            return [
                'runner' => 'composer',
                'display' => $normalized,
                'argv' => $argv,
            ];
        }

        throw new InvalidArgumentException('Only "php artisan …" and "composer …" commands are allowed.');
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function assertAllowed(string $runner, array $tokens): void
    {
        $patterns = self::allowlistFor($runner);

        if (in_array('*', $patterns, true)) {
            return;
        }

        $name = self::subCommandName($tokens);

        if ($name === null) {
            throw new InvalidArgumentException(
                sprintf('A %s sub-command is required; bare options are not allowed.', $runner)
            );
        }

        foreach ($patterns as $pattern) {
            if ($pattern === $name) {
                return;
            }

            if (str_ends_with($pattern, '*') && str_starts_with($name, substr($pattern, 0, -1))) {
                return;
            }
        }

        throw new InvalidArgumentException(sprintf(
            '"%s %s" is not allowed. Add it to config("mksine.console_terminal.allowed_commands.%s") to permit it.',
            $runner,
            $name,
            $runner,
        ));
    }

    /**
     * @return list<string>
     */
    private static function allowlistFor(string $runner): array
    {
        $configured = config("mksine.console_terminal.allowed_commands.{$runner}");

        if (! is_array($configured)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), $configured),
            static fn (string $value): bool => $value !== '',
        ));
    }

    /**
     * The first non-option token, which is the command both Artisan and Composer dispatch on.
     *
     * @param  list<string>  $tokens
     */
    private static function subCommandName(array $tokens): ?string
    {
        foreach ($tokens as $token) {
            if (! str_starts_with($token, '-')) {
                return strtolower($token);
            }
        }

        return null;
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
