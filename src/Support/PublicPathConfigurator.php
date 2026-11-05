<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;

/**
 * Points Laravel's public path at a web root outside the project (e.g. cPanel's public_html).
 *
 * Applied from the service provider so artisan commands that publish assets write to the
 * same directory the web server serves, not only HTTP requests. Config files are loaded
 * before providers register, so values already built from public_path() (disk roots,
 * storage links) are moved to the new directory too.
 */
final class PublicPathConfigurator
{
    public static function apply(Application $app, mixed $configuredPath): void
    {
        $resolved = self::resolve($app->basePath(), $configuredPath);

        $previous = $app->publicPath();

        if ($resolved === null || $resolved === $previous) {
            return;
        }

        $app->usePublicPath($resolved);

        if ($app->bound('config')) {
            self::rebaseConfiguredPaths($app->make('config'), $previous, $resolved);
        }
    }

    /**
     * Move `filesystems.disks.*.root` and `filesystems.links` entries under the old public path.
     */
    public static function rebaseConfiguredPaths(Repository $config, string $from, string $to): void
    {
        $rebase = static function (mixed $path) use ($from, $to): mixed {
            if (! is_string($path) || ($path !== $from && ! str_starts_with($path, $from . DIRECTORY_SEPARATOR) && ! str_starts_with($path, $from . '/'))) {
                return $path;
            }

            return $to . substr($path, strlen($from));
        };

        foreach ((array) $config->get('filesystems.disks', []) as $disk => $settings) {
            if (is_array($settings) && isset($settings['root'])) {
                $config->set("filesystems.disks.{$disk}.root", $rebase($settings['root']));
            }
        }

        $links = [];
        foreach ((array) $config->get('filesystems.links', []) as $link => $target) {
            $links[(string) $rebase((string) $link)] = $target;
        }
        $config->set('filesystems.links', $links);
    }

    /**
     * Resolve an absolute or project-relative directory. Returns null when unset or missing on disk.
     */
    public static function resolve(string $basePath, mixed $configuredPath): ?string
    {
        if (! is_string($configuredPath) || trim($configuredPath) === '') {
            return null;
        }

        $path = trim($configuredPath);

        if (! self::isAbsolute($path)) {
            $path = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $path;
        }

        $real = realpath($path);

        return $real !== false && is_dir($real) ? $real : null;
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }
}
