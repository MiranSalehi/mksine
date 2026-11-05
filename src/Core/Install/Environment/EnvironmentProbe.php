<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Environment;

use Closure;

/**
 * Detects disabled functions, time limits and the web root layout. No Laravel dependency.
 */
final class EnvironmentProbe
{
    private readonly Closure $iniGet;

    public function __construct(?Closure $iniGet = null)
    {
        $this->iniGet = $iniGet ?? ini_get(...);
    }

    /**
     * @param  string|null  $documentRoot  $_SERVER['DOCUMENT_ROOT'] for web requests; null on the CLI.
     */
    public function probe(string $publicPath, ?string $documentRoot = null): EnvironmentReport
    {
        $documentRoot = is_string($documentRoot) && $documentRoot !== '' ? $documentRoot : null;

        return new EnvironmentReport(
            canExec: $this->functionAvailable('exec'),
            canProcOpen: $this->functionAvailable('proc_open'),
            canSymlink: $this->functionAvailable('symlink'),
            maxExecutionTime: (int) ($this->iniGet)('max_execution_time'),
            memoryLimit: (string) ($this->iniGet)('memory_limit'),
            documentRoot: $documentRoot,
            publicPath: $publicPath,
            publicPathIsDocumentRoot: $documentRoot === null ? null : self::samePath($documentRoot, $publicPath),
        );
    }

    public function functionAvailable(string $function): bool
    {
        if (! function_exists($function)) {
            return false;
        }

        $disabled = array_map('trim', explode(',', strtolower((string) ($this->iniGet)('disable_functions'))));

        return ! in_array(strtolower($function), $disabled, true);
    }

    /**
     * True when the application folder sits inside the web root (but is not the public folder
     * itself), so files such as .env could be requested over HTTP.
     */
    public static function projectIsInsideWebRoot(string $basePath, string $publicPath, ?string $documentRoot): bool
    {
        $root = $documentRoot === null || $documentRoot === '' ? false : realpath($documentRoot);
        $base = realpath($basePath);

        return $root !== false && $base !== false && $root !== realpath($publicPath)
            && str_starts_with($base . DIRECTORY_SEPARATOR, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    }

    private static function samePath(string $first, string $second): bool
    {
        $normalize = static fn (string $path): string => rtrim(str_replace('\\', '/', realpath($path) ?: $path), '/');

        return $normalize($first) === $normalize($second);
    }
}
