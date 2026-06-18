<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console\Interactive;

use InvalidArgumentException;
use Miran\Mksine\Support\Console\AdminConsoleCommandParser;

final class AdminInteractiveCommandResolver
{
    /**
     * @return list<string>
     */
    public static function interactiveCommandNames(): array
    {
        return [
            'migrate:smart',
        ];
    }

    public static function isInteractiveInput(string $commandInput, string $projectRoot): bool
    {
        try {
            $parsed = AdminConsoleCommandParser::parse($commandInput, $projectRoot);
        } catch (InvalidArgumentException) {
            return false;
        }

        return self::isInteractiveArgv($parsed['argv']);
    }

    /**
     * @param  list<string>  $argv
     */
    public static function isInteractiveArgv(array $argv): bool
    {
        $subcommand = self::artisanSubcommand($argv);

        if ($subcommand === null || ! in_array($subcommand, self::interactiveCommandNames(), true)) {
            return false;
        }

        if (in_array('--all', $argv, true)) {
            return false;
        }

        if (in_array('--no-interaction', $argv, true)) {
            return false;
        }

        if (self::hasOptionValue($argv, '--migration')) {
            return false;
        }

        return true;
    }

    public static function handlerKey(string $commandInput, string $projectRoot): ?string
    {
        if (! self::isInteractiveInput($commandInput, $projectRoot)) {
            return null;
        }

        $parsed = AdminConsoleCommandParser::parse($commandInput, $projectRoot);

        return self::artisanSubcommand($parsed['argv']);
    }

    /**
     * @param  list<string>  $argv
     */
    public static function artisanSubcommand(array $argv): ?string
    {
        $subcommand = $argv[2] ?? null;

        return is_string($subcommand) && $subcommand !== '' ? $subcommand : null;
    }

    /**
     * @param  list<string>  $argv
     */
    private static function hasOptionValue(array $argv, string $option): bool
    {
        foreach ($argv as $index => $token) {
            if ($token === $option) {
                return isset($argv[$index + 1]) && ! str_starts_with((string) $argv[$index + 1], '-');
            }

            if (str_starts_with($token, $option.'=')) {
                return substr($token, strlen($option) + 1) !== '';
            }
        }

        return false;
    }
}
