<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Closure;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;

/**
 * Builds the application a public release ships: a fresh Laravel skeleton with MKSine
 * and the official plugins installed, and nothing from the maintainer's own project.
 *
 * Steps: `composer create-project laravel/laravel`, a filtered copy of this package as a
 * path repository, `composer update --no-dev`, `filament:install --panels`, the file steps
 * of `mksine:install`, the plugins, then cleanup. Every command runs with a clean
 * environment so nothing from the maintainer's .env (database, keys) leaks in.
 */
final class ReleaseSkeleton
{
    public const LARAVEL_CONSTRAINT = '^12.0';

    public const RELEASE_REPOSITORY = 'https://github.com/MiranSalehi/mksine';

    /**
     * The only top-level entries a release zip may contain.
     *
     * @var list<string>
     */
    public const TOP_LEVEL_ALLOWLIST = [
        'app', 'bootstrap', 'config', 'database', 'lang', 'plugins', 'public', 'resources',
        'routes', 'storage', 'vendor', 'artisan', 'composer.json', '.env.example',
    ];

    /**
     * Plugins (folder names under plugins_path) packed into public releases: the ones
     * published on mksine.com/marketplace. A plugin that is not listed never reaches a
     * download, so a new or private plugin (ecom, mks-booking, mks-notification) cannot leak.
     * Add a plugin here only when it is published.
     *
     * @var list<string>
     */
    public const PUBLISHED_PLUGINS = [
        'mks-ai',
        'mks-analytics',
        'mks-automations',
        'mks-backup',
        'mks-content-types',
        'mks-custom-fields',
        'mks-developer-tools',
        'mks-import-export',
        'mks-mail',
        'mks-membership',
        'mks-redirects',
    ];

    /** Environment variables kept for child processes; everything else is removed. */
    private const INHERITED_ENVIRONMENT = [
        'PATH', 'HOME', 'USER', 'LOGNAME', 'SHELL', 'TMPDIR', 'TEMP', 'TMP', 'LANG', 'LC_ALL',
        'COMPOSER_HOME', 'COMPOSER_CACHE_DIR', 'COMPOSER_AUTH', 'SystemRoot', 'ComSpec', 'PATHEXT', 'APPDATA', 'LOCALAPPDATA',
    ];

    /**
     * @param  list<string>  $composer  Composer argv prefix ({@see ComposerBinary::argv()}).
     * @param  Closure(string): void  $log
     * @param  list<string>  $plugins  Plugin folders to pack ({@see self::releasePlugins()}).
     */
    public function __construct(
        private readonly string $packagePath,
        private readonly string $pluginsPath,
        private readonly string $stagingPath,
        private readonly array $composer,
        private readonly string $version,
        private readonly Closure $log,
        private readonly string $laravelConstraint = self::LARAVEL_CONSTRAINT,
        private readonly array $plugins = [],
    ) {}

    /**
     * The plugins to pack, checked to exist in the plugins folder.
     *
     * @return list<string>
     *
     * @throws RuntimeException When the list is empty, malformed or names a missing plugin.
     */
    public static function releasePlugins(string $pluginsPath, mixed $configured = self::PUBLISHED_PLUGINS): array
    {
        $plugins = is_array($configured) ? array_values(array_unique(array_filter($configured, 'is_string'))) : [];

        if ($plugins === []) {
            throw new RuntimeException('No release plugins are listed.');
        }

        foreach ($plugins as $plugin) {
            if (preg_match('/^[A-Za-z0-9._-]+$/', $plugin) !== 1 || in_array($plugin, ['.', '..'], true) || ! is_file($pluginsPath . '/' . $plugin . '/plugin.php')) {
                throw new RuntimeException("Release plugin \"{$plugin}\" was not found in {$pluginsPath}.");
            }
        }

        return $plugins;
    }

    public function build(): void
    {
        if (is_dir($this->stagingPath) && (new \FilesystemIterator($this->stagingPath))->valid()) {
            throw new RuntimeException("Staging directory is not empty: {$this->stagingPath}");
        }

        ($this->log)("Creating a fresh Laravel {$this->laravelConstraint} application…");
        $this->run([...$this->composer, 'create-project', 'laravel/laravel', $this->stagingPath, $this->laravelConstraint, '--no-install', '--no-scripts', '--prefer-dist', '--no-interaction'], dirname($this->stagingPath));

        ($this->log)('Adding MKSine…');
        self::copyFiltered($this->packagePath, $this->stagingPath . '/packages/mksine', self::shouldCopyPackagePath(...));
        $this->useLocalPackage();

        ($this->log)('Installing production Composer dependencies…');
        $this->run([...$this->composer, 'update', '--no-dev', '--optimize-autoloader', '--no-scripts', '--no-interaction', '--no-progress']);

        File::copy($this->stagingPath . '/.env.example', $this->stagingPath . '/.env');
        $this->artisan(['key:generate', '--force']);
        $this->artisan(['package:discover']);

        ($this->log)('Creating the admin panel…');
        $this->artisan(['filament:install', '--panels', '--no-interaction']);

        ($this->log)('Running the MKSine file setup…');
        $this->artisan(['mksine:install', '--no-interaction', '--profile=shared']);

        ($this->log)('Adding the published plugins: ' . implode(', ', $this->plugins) . '…');
        foreach ($this->plugins as $plugin) {
            self::copyFiltered(
                $this->pluginsPath . '/' . $plugin,
                $this->stagingPath . '/plugins/' . $plugin,
                fn (string $relativePath): bool => self::shouldCopyPluginPath($plugin . '/' . $relativePath),
            );
        }
        // Plugin discovery was cached (bootstrap/cache) before the plugins existed.
        $this->artisan(['mks-plugin:discover']);
        $this->artisan(['mks-plugin:publish']);
        $this->artisan(['optimize:clear']);

        $this->cleanUp();
        $this->useReleaseRepository();
    }

    /**
     * Files of this package that a running site needs (no docs, tests, build tooling or sources).
     */
    public static function shouldCopyPackagePath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', $relativePath);

        foreach (['node_modules/', 'vendor/', '.git/', '.github/', 'docs/', 'tests/', 'build/', 'bin/', 'scripts/'] as $directory) {
            if (str_starts_with($relativePath, $directory) || str_contains($relativePath, '/' . $directory)) {
                return false;
            }
        }

        if (preg_match('#^resources/views/themes/[^/]+/src/#', $relativePath) === 1 || str_starts_with($relativePath, 'resources/installer/')) {
            return false;
        }

        return ! self::isToolingFile($relativePath);
    }

    /**
     * Plugin files a running site needs. A plugin's own vendor/ is kept: some plugins load
     * libraries from it.
     */
    public static function shouldCopyPluginPath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $insidePlugin = (string) preg_replace('#^[^/]+/#', '', $relativePath);

        if (str_starts_with($insidePlugin, 'vendor/')) {
            return basename($relativePath) !== '.DS_Store';
        }

        foreach (['node_modules/', '.git/', 'docs/', 'tests/', 'load-tests/'] as $directory) {
            if (str_starts_with($insidePlugin, $directory) || str_contains($insidePlugin, '/' . $directory)) {
                return false;
            }
        }

        return ! self::isToolingFile($relativePath) && ! str_ends_with($relativePath, '.zip');
    }

    /**
     * Whether a project-relative path may be packed into a release zip.
     */
    public static function isAllowedTopLevel(string $relativePath): bool
    {
        $first = explode('/', str_replace('\\', '/', ltrim($relativePath, '/')), 2)[0];

        return in_array($first, self::TOP_LEVEL_ALLOWLIST, true);
    }

    /**
     * Environment for child processes: every inherited variable removed except the basics,
     * so the skeleton never sees the maintainer's APP_*, DB_* or other settings.
     *
     * @return array<string, string|false>
     */
    public static function cleanEnvironment(): array
    {
        $environment = [];

        foreach (array_keys(getenv() + $_ENV + $_SERVER) as $key) {
            if (! is_string($key) || $key === '' || in_array($key, self::INHERITED_ENVIRONMENT, true)) {
                continue;
            }

            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) === 1) {
                $environment[$key] = false;
            }
        }

        return $environment;
    }

    private static function isToolingFile(string $relativePath): bool
    {
        $basename = basename($relativePath);

        if (in_array($basename, ['.DS_Store', 'Thumbs.db', 'package.json', 'package-lock.json', 'composer.lock', 'vite.config.js', 'tailwind.config.js', 'postcss.config.js', 'phpunit.xml', 'phpunit.xml.dist', 'phpstan.neon', 'phpstan.neon.dist', 'phpstan-baseline.neon', 'pint.json', '.editorconfig', '.gitattributes', '.gitignore'], true)) {
            return true;
        }

        if (str_contains($basename, '.mksine-backup-')) {
            return true;
        }

        // Markdown is documentation; licences stay.
        return str_ends_with(strtolower($basename), '.md') && ! str_starts_with(strtoupper($basename), 'LICENSE');
    }

    /**
     * @param  Closure(string): bool  $include  Receives the path relative to $source.
     */
    public static function copyFiltered(string $source, string $target, Closure $include): int
    {
        $source = rtrim(str_replace('\\', '/', (string) realpath($source)), '/');
        $copied = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $relative = substr(str_replace('\\', '/', $file->getPathname()), strlen($source) + 1);

            if (! $include($relative)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($target . '/' . $relative));
            File::copy($file->getPathname(), $target . '/' . $relative);
            $copied++;
        }

        return $copied;
    }

    private function useLocalPackage(): void
    {
        $composer = $this->readComposerJson();
        $composer['repositories'] = [['type' => 'path', 'url' => './packages/mksine', 'options' => ['symlink' => false, 'versions' => ['miran/mksine' => $this->version]]]];
        $composer['require']['php'] = '^8.4';
        $composer['require']['miran/mksine'] = $this->version;
        $this->writeComposerJson($composer);
    }

    /**
     * The shipped composer.json installs MKSine from GitHub, so a server with Composer can
     * run `composer update` later. composer.lock is not shipped: it records the local path.
     */
    private function useReleaseRepository(): void
    {
        $composer = $this->readComposerJson();
        $composer['repositories'] = [['type' => 'vcs', 'url' => self::RELEASE_REPOSITORY]];
        $composer['require']['miran/mksine'] = '^' . $this->version;
        $composer['name'] = 'miran/mksine-app';
        $composer['description'] = 'MKSine — Laravel + Filament CMS.';
        unset($composer['scripts']['dev'], $composer['scripts']['test'], $composer['scripts']['setup']);
        $this->writeComposerJson($composer);
    }

    private function cleanUp(): void
    {
        $paths = [
            '.env', 'composer.lock', 'packages', 'README.md', 'package.json', 'vite.config.js', 'phpunit.xml', 'tests',
            'resources/js', 'resources/css', 'resources/views/welcome.blade.php', 'database/database.sqlite',
        ];

        foreach ($paths as $path) {
            $absolute = $this->stagingPath . '/' . $path;
            is_dir($absolute) && ! is_link($absolute) ? File::deleteDirectory($absolute) : File::delete($absolute);
        }

        foreach (File::allFiles($this->stagingPath . '/app') as $file) {
            if (str_contains($file->getFilename(), '.mksine-backup-')) {
                File::delete($file->getPathname());
            }
        }

        foreach ([$this->stagingPath . '/vendor', ...glob($this->stagingPath . '/plugins/*/vendor', GLOB_ONLYDIR) ?: []] as $vendor) {
            self::stripVendorDocumentation($vendor);
        }

        // Laravel expects resources/views to exist; an empty folder would not survive zipping.
        File::ensureDirectoryExists($this->stagingPath . '/resources/views');
        File::put($this->stagingPath . '/resources/views/.gitkeep', '');
    }

    /**
     * Remove READMEs, changelogs and doc folders from Composer packages. Code, licences and
     * anything a package loads at runtime stay.
     */
    public static function stripVendorDocumentation(string $vendorPath): int
    {
        if (! is_dir($vendorPath)) {
            return 0;
        }

        $removed = 0;

        foreach (glob($vendorPath . '/*/*', GLOB_ONLYDIR) ?: [] as $package) {
            foreach (['.github', 'docs', 'doc'] as $directory) {
                if (is_dir($package . '/' . $directory)) {
                    File::deleteDirectory($package . '/' . $directory);
                    $removed++;
                }
            }
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendorPath, RecursiveDirectoryIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $name = $file->getFilename();
            $lower = strtolower($name);

            $isDocumentation = (str_ends_with($lower, '.md') || str_ends_with($lower, '.rst')) && ! str_starts_with($lower, 'license');

            if ($file->isFile() && ($isDocumentation || $name === '.DS_Store')) {
                File::delete($file->getPathname());
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @return array<string, mixed>
     */
    private function readComposerJson(): array
    {
        $decoded = json_decode((string) file_get_contents($this->stagingPath . '/composer.json'), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('The skeleton composer.json could not be read.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $composer
     */
    private function writeComposerJson(array $composer): void
    {
        file_put_contents($this->stagingPath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    /**
     * @param  list<string>  $arguments
     */
    private function artisan(array $arguments): void
    {
        $this->run([PHP_BINARY, '-d', 'memory_limit=1G', 'artisan', ...$arguments]);
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command, ?string $workingDirectory = null): void
    {
        $process = new Process($command, $workingDirectory ?? $this->stagingPath, self::cleanEnvironment(), null, 900);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                "`%s` failed:\n%s",
                implode(' ', array_slice($command, 0, 4)),
                trim($process->getErrorOutput() ?: $process->getOutput()),
            ));
        }
    }
}
