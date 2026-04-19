<?php

namespace Miran\Mksine\Support;

use Illuminate\Support\Str;

class ReleaseArchiveBuildRoots
{
    /**
     * Ordered directories (absolute paths) where `npm run build` should run.
     *
     * @return list<string>
     */
    public static function discover(string $basePath): array
    {
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $roots = [];

        $mksineThemePath = $basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'mksine'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'mksine';
        if (self::packageHasBuildScript($mksineThemePath.DIRECTORY_SEPARATOR.'package.json')) {
            $roots[] = $mksineThemePath;
        }

        $mksinePackagePath = $basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'mksine';
        if (self::packageHasBuildScript($mksinePackagePath.DIRECTORY_SEPARATOR.'package.json')) {
            $roots[] = $mksinePackagePath;
        }

        $projectThemesDir = $basePath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'themes';
        foreach (self::sortedChildDirsWithPackageJson($projectThemesDir) as $dir) {
            if (realpath($dir) === realpath($mksineThemePath)) {
                continue;
            }
            $roots[] = $dir;
        }

        $pluginsDir = $basePath.DIRECTORY_SEPARATOR.'plugins';
        foreach (self::pluginRootsWithBuild($pluginsDir) as $dir) {
            $roots[] = $dir;
        }

        $rootPackage = $basePath.DIRECTORY_SEPARATOR.'package.json';
        if (self::packageHasBuildScript($rootPackage)) {
            $roots[] = $basePath;
        }

        return $roots;
    }

    public static function packageHasBuildScript(string $packageJsonPath): bool
    {
        if (! is_file($packageJsonPath)) {
            return false;
        }

        $json = json_decode((string) file_get_contents($packageJsonPath), true);
        if (! is_array($json) || ! isset($json['scripts']) || ! is_array($json['scripts'])) {
            return false;
        }

        return isset($json['scripts']['build']) && is_string($json['scripts']['build']) && $json['scripts']['build'] !== '';
    }

    /**
     * @return list<string>
     */
    private static function sortedChildDirsWithPackageJson(string $parentDir): array
    {
        if (! is_dir($parentDir)) {
            return [];
        }

        $dirs = [];
        foreach (scandir($parentDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $child = $parentDir.DIRECTORY_SEPARATOR.$entry;
            if (! is_dir($child)) {
                continue;
            }
            if (self::packageHasBuildScript($child.DIRECTORY_SEPARATOR.'package.json')) {
                $dirs[] = $child;
            }
        }
        sort($dirs);

        return $dirs;
    }

    /**
     * Top-level plugin folders only: plugins/{id}/package.json with a build script.
     *
     * @return list<string>
     */
    private static function pluginRootsWithBuild(string $pluginsDir): array
    {
        if (! is_dir($pluginsDir)) {
            return [];
        }

        $dirs = [];
        foreach (scandir($pluginsDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $child = $pluginsDir.DIRECTORY_SEPARATOR.$entry;
            if (! is_dir($child)) {
                continue;
            }
            if (self::packageHasBuildScript($child.DIRECTORY_SEPARATOR.'package.json')) {
                $dirs[] = $child;
            }
        }
        sort($dirs);

        return $dirs;
    }

    /**
     * Whether a path relative to project root should be included in the release zip.
     */
    public static function shouldIncludeInZip(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim($relativePath, '/');

        if ($relativePath === '') {
            return false;
        }

        if (Str::contains($relativePath, 'node_modules/') || Str::endsWith($relativePath, '/node_modules') || $relativePath === 'node_modules') {
            return false;
        }

        if (Str::startsWith($relativePath, '.git/') || Str::contains($relativePath, '/.git/') || $relativePath === '.git') {
            return false;
        }

        if (Str::startsWith($relativePath, 'mksine-setup/') || $relativePath === 'mksine-setup') {
            return false;
        }

        $basename = basename($relativePath);
        if (self::isExcludedEnvFile($basename)) {
            return false;
        }

        if (Str::startsWith($relativePath, 'public/')) {
            return self::isPublicPathAllowed($relativePath);
        }

        return true;
    }

    private static function isExcludedEnvFile(string $basename): bool
    {
        if ($basename === '.env') {
            return true;
        }

        if ($basename === '.env.example') {
            return false;
        }

        return Str::startsWith($basename, '.env.');
    }

    private static function isPublicPathAllowed(string $relativePath): bool
    {
        $rest = Str::after($relativePath, 'public/');

        $allowedPrefixes = [
            'build/',
            'themes/',
            'vendor/mksine/',
            'css/',
            'js/',
            'fonts/',
            'plugins/',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if ($rest === rtrim($prefix, '/') || Str::startsWith($rest, $prefix)) {
                return true;
            }
        }

        $publicRootBasenames = ['index.php', '.htaccess', 'robots.txt'];
        if (! Str::contains($rest, '/')) {
            if (in_array($rest, $publicRootBasenames, true)) {
                return true;
            }
            if (Str::startsWith($rest, 'favicon')) {
                return true;
            }
        }

        return false;
    }
}
