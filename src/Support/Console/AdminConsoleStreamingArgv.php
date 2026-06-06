<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

/**
 * Tweaks argv so long-running Artisan commands flush line output to the console log.
 */
final class AdminConsoleStreamingArgv
{
    /**
     * @param  list<string>  $argv
     * @return list<string>
     */
    public static function enhance(array $argv): array
    {
        $enhanced = $argv;

        if ($enhanced !== [] && self::looksLikePhpBinary($enhanced[0])) {
            array_splice($enhanced, 1, 0, ['-d', 'output_buffering=0', '-d', 'implicit_flush=1']);
        }

        $joined = implode(' ', $enhanced);

        if (preg_match('/\bqueue:work\b/', $joined) === 1 && ! preg_match('/(?:^|\s)(?:-v+|--verbose)(?:\s|$)/', $joined)) {
            $enhanced[] = '--verbose';
        }

        if (preg_match('/\bschedule:work\b/', $joined) === 1 && ! preg_match('/(?:^|\s)(?:-v+|--verbose)(?:\s|$)/', $joined)) {
            $enhanced[] = '--verbose';
        }

        return $enhanced;
    }

    private static function looksLikePhpBinary(string $path): bool
    {
        if ($path === 'php') {
            return true;
        }

        return str_contains(strtolower($path), 'php') && ! str_contains(strtolower($path), 'fpm');
    }
}
