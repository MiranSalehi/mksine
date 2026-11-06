<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Closure;
use Symfony\Component\Process\Process;

/**
 * Runs `npm run build` in each build root, installing npm dependencies first when a root
 * has no node_modules.
 */
final class ReleaseAssetBuilder
{
    /**
     * @param  list<string>  $roots
     * @param  Closure(string): void  $write  Receives progress lines and command output.
     * @return string|null An error message, or null when every build succeeded.
     */
    public static function build(string $basePath, array $roots, Closure $write): ?string
    {
        foreach ($roots as $root) {
            $label = str_replace($basePath . DIRECTORY_SEPARATOR, '', $root) ?: '(project root)';

            if (! is_dir($root . DIRECTORY_SEPARATOR . 'node_modules')) {
                $install = is_file($root . DIRECTORY_SEPARATOR . 'package-lock.json') ? ['npm', 'ci'] : ['npm', 'install'];
                $write("Installing npm dependencies in {$label}…\n");

                if (! self::run($install, $root, $write)) {
                    return implode(' ', $install) . ' failed in ' . $root;
                }
            }

            $write("Running npm run build in {$label}…\n");

            if (! self::run(['npm', 'run', 'build'], $root, $write)) {
                return 'npm run build failed in ' . $root;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $command
     * @param  Closure(string): void  $write
     */
    private static function run(array $command, string $workingDirectory, Closure $write): bool
    {
        $process = new Process($command, $workingDirectory, null, null, 0);
        $process->run(fn ($type, string $buffer) => $write($buffer));

        return $process->isSuccessful();
    }
}
