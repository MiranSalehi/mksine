<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

use Illuminate\Support\Facades\Artisan;
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
     * Options that may come before the sub-command. They take no value, so the next token is
     * always the command; `--env tinker`-style input could otherwise hide the real command
     * behind an option value.
     *
     * @var list<string>
     */
    private const LEADING_FLAGS = ['-n', '--no-interaction', '-q', '--quiet', '--ansi', '--no-ansi', '-v', '-vv', '-vvv', '--verbose'];

    /**
     * Options that change which code a runner loads: another `.env` file, another project.
     *
     * @var array<string, list<string>>
     */
    private const FORBIDDEN_OPTIONS = [
        'artisan' => ['--env'],
        'composer' => ['-d', '--working-dir'],
    ];

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
            $tokens = self::assertAllowed('artisan', $tokens);

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
            $tokens = self::assertAllowed('composer', $tokens);

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
     * Whether a sub-command (e.g. `migrate:status`) is on the runner's allowlist.
     */
    public static function isAllowed(string $runner, string $subCommand): bool
    {
        $patterns = self::allowlistFor($runner);

        if (in_array('*', $patterns, true)) {
            return true;
        }

        $name = strtolower(trim($subCommand));

        if ($name === '') {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern === $name) {
                return true;
            }

            if (str_ends_with($pattern, '*') && str_starts_with($name, substr($pattern, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks the sub-command against the allowlist and returns the tokens to run.
     *
     * Artisan resolves abbreviations (`mks:fresh-super` → `mksine:fresh-super-admin`), so the
     * name must be a registered command or alias, and the token is replaced with the command's
     * canonical name so the process runs exactly what was checked.
     *
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private static function assertAllowed(string $runner, array $tokens): array
    {
        if (in_array('*', self::allowlistFor($runner), true)) {
            return $tokens;
        }

        $index = self::subCommandIndex($runner, $tokens);
        self::assertNoForbiddenOptions($runner, $tokens);

        if ($index === null) {
            throw new InvalidArgumentException(
                sprintf('A %s sub-command is required; bare options are not allowed.', $runner)
            );
        }

        $name = strtolower($tokens[$index]);

        if ($runner === 'artisan') {
            $canonical = self::canonicalArtisanCommand($name);

            if ($canonical === null) {
                throw new InvalidArgumentException(sprintf(
                    '"artisan %s" is not a registered command. Use the full command name.',
                    $name,
                ));
            }

            if (! self::isAllowed($runner, $name) || ! self::isAllowed($runner, $canonical)) {
                throw self::notAllowed($runner, $canonical);
            }

            $tokens[$index] = $canonical;

            return $tokens;
        }

        if (! self::isAllowed($runner, $name)) {
            throw self::notAllowed($runner, $name);
        }

        return $tokens;
    }

    private static function notAllowed(string $runner, string $name): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            '"%s %s" is not allowed. Add it to config("mksine.console_terminal.allowed_commands.%s") to permit it.',
            $runner,
            $name,
            $runner,
        ));
    }

    /**
     * Name of the registered Artisan command (or alias target) the input names exactly.
     */
    private static function canonicalArtisanCommand(string $name): ?string
    {
        foreach (Artisan::all() as $registeredName => $command) {
            if (strtolower((string) $registeredName) === $name) {
                return strtolower((string) $command->getName());
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function assertNoForbiddenOptions(string $runner, array $tokens): void
    {
        foreach ($tokens as $token) {
            $option = strtolower(explode('=', $token, 2)[0]);

            foreach (self::FORBIDDEN_OPTIONS[$runner] ?? [] as $forbidden) {
                $isShortWithValue = ! str_starts_with($forbidden, '--') && str_starts_with($option, $forbidden);

                if ($option === $forbidden || $isShortWithValue) {
                    throw new InvalidArgumentException(sprintf('The %s option is not allowed here.', $forbidden));
                }
            }
        }
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
     * Position of the sub-command: the first token, after value-less flags only.
     *
     * @param  list<string>  $tokens
     */
    private static function subCommandIndex(string $runner, array $tokens): ?int
    {
        foreach ($tokens as $index => $token) {
            if (! str_starts_with($token, '-')) {
                return $index;
            }

            if (! in_array(strtolower($token), self::LEADING_FLAGS, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Put the %s sub-command first; "%s" is not allowed before it.',
                    $runner,
                    $token,
                ));
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
