<?php

namespace Miran\Mksine\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ReleaseArchiveBuildRoots
{
    /**
     * Build roots for the core application only.
     *
     * Theme and plugin sources are installed separately on the server; they are not
     * packed by {@see shouldIncludeInZip()} and are not built here.
     *
     * @return list<string>
     */
    public static function discover(string $basePath): array
    {
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $roots = [];

        $mksinePackagePath = $basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'mksine';
        if (self::packageHasBuildScript($mksinePackagePath.DIRECTORY_SEPARATOR.'package.json')) {
            $roots[] = $mksinePackagePath;
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
     * Whether a path relative to project root should be included in the release zip.
     */
    public static function shouldIncludeInZip(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim($relativePath, '/');

        if ($relativePath === '') {
            return false;
        }

        if (self::isExcludedExtensionPackPath($relativePath)) {
            return false;
        }

        if (self::isExcludedUploadsPath($relativePath)) {
            return false;
        }

        if (self::isExcludedStorageRuntimePath($relativePath)) {
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

        if (Str::startsWith($relativePath, 'bootstrap/cache/')) {
            return basename($relativePath) === '.gitignore';
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

    /**
     * Project theme source paths that must ship inside the release zip (e.g. composer PSR-4).
     *
     * @see config('mksine.release_archive.include_theme_paths')
     */
    public static function isIncludedProjectThemePath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        /** @var list<string> $paths */
        $paths = config('mksine.release_archive.include_theme_paths', []);

        foreach ($paths as $path) {
            $path = str_replace('\\', '/', trim($path, '/'));
            if ($path === '' || $relativePath === '') {
                continue;
            }

            if ($relativePath === $path || Str::startsWith($relativePath, $path.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Theme and plugin trees are deployed separately (zip updater / manual install).
     */
    public static function isExcludedExtensionPackPath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        if ($relativePath === 'plugins' || Str::startsWith($relativePath, 'plugins/')) {
            return true;
        }

        if (self::isIncludedProjectThemePath($relativePath)) {
            return false;
        }

        if ($relativePath === 'resources/views/themes' || Str::startsWith($relativePath, 'resources/views/themes/')) {
            return true;
        }

        $packageThemes = 'packages/mksine/resources/views/themes';

        return $relativePath === $packageThemes || Str::startsWith($relativePath, $packageThemes.'/');
    }

    /**
     * User media, Woo import uploads, and any nested {@code uploads/} directory.
     */
    public static function isExcludedUploadsPath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        if ($relativePath === 'uploads' || Str::startsWith($relativePath, 'uploads/')) {
            return true;
        }

        if ($relativePath === 'public/uploads' || Str::startsWith($relativePath, 'public/uploads/')) {
            return true;
        }

        return Str::contains($relativePath, '/uploads/') || Str::endsWith($relativePath, '/uploads');
    }

    /**
     * Runtime data under {@code storage/} (logs, sessions, Woo import zips, Livewire temp, etc.).
     * Only {@code .gitignore} scaffold files are kept so empty dirs exist on deploy.
     */
    public static function isExcludedStorageRuntimePath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        if (! Str::startsWith($relativePath, 'storage/')) {
            return false;
        }

        if (basename($relativePath) === '.gitignore') {
            return false;
        }

        if ($relativePath === 'storage/logs' || Str::startsWith($relativePath, 'storage/logs/')) {
            return true;
        }

        if ($relativePath === 'storage/framework' || Str::startsWith($relativePath, 'storage/framework/')) {
            return true;
        }

        if ($relativePath === 'storage/app' || Str::startsWith($relativePath, 'storage/app/')) {
            return true;
        }

        return false;
    }

    /**
     * Copy package migrations into {@code database/migrations/} so production
     * {@code php artisan migrate} picks them up from the release zip (same set as
     * {@code vendor:publish --tag=mksine-migrations} / {@code mksine:install}).
     *
     * @return int Number of files written or updated
     */
    public static function syncPackageMigrationsToApp(string $basePath): int
    {
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $source = $basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'mksine'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        $target = $basePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';

        if (! is_dir($source)) {
            return 0;
        }

        File::ensureDirectoryExists($target);

        $written = 0;

        foreach (File::files($source) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $destination = $target.DIRECTORY_SEPARATOR.$file->getFilename();

            if (is_file($destination) && hash_file('sha256', $file->getRealPath()) === hash_file('sha256', $destination)) {
                continue;
            }

            File::copy($file->getRealPath(), $destination);
            $written++;
        }

        return $written;
    }

    /**
     * Remove compiled bootstrap cache files so the deployed app rebuilds against its environment.
     */
    public static function clearBootstrapCache(string $basePath): void
    {
        $cacheDir = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache';
        if (! is_dir($cacheDir)) {
            return;
        }

        foreach (glob($cacheDir.DIRECTORY_SEPARATOR.'*.php') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
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
            'vendor/mksine/',
            'css/',
            'js/',
            'fonts/',
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
