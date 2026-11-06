<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Support\ComposerBinary;
use Miran\Mksine\Support\PackageVersion;
use Miran\Mksine\Support\QuickInstaller;
use Miran\Mksine\Support\ReleaseArchiveBuildRoots;
use Miran\Mksine\Support\ReleaseArchiveOptions;
use Miran\Mksine\Support\ReleaseArchiveZipper;
use Miran\Mksine\Support\ReleaseAssetBuilder;
use Miran\Mksine\Support\ReleaseManifest;
use Miran\Mksine\Support\ReleaseSkeleton;
use Throwable;

/**
 * Builds a public release from a clean Laravel application with MKSine and the published
 * plugins ({@see ReleaseSkeleton::PUBLISHED_PLUGINS}) — never from the maintainer's own project — and writes the shared-hosting and
 * server zips, the quick installer (mksine.php), SHA256SUMS and release.json.
 */
class ReleaseBuildCommand extends Command
{
    protected $signature = 'mks:release-build
                            {--output-dir= : Directory for the release files (default storage/app/releases/{version})}
                            {--laravel=' . ReleaseSkeleton::LARAVEL_CONSTRAINT . ' : Laravel version constraint for the skeleton}
                            {--skip-build : Skip npm builds of the package and plugins}
                            {--keep-staging : Keep the staging application for inspection}';

    protected $description = 'Build a clean MKSine release (shared-hosting and server zips, quick installer, SHA256SUMS, release.json)';

    public function handle(): int
    {
        $version = PackageVersion::current();
        $basePath = base_path();
        $composer = ComposerBinary::argv($basePath);

        if ($composer === null) {
            $this->error('Composer was not found; it is needed to build the release application.');

            return self::FAILURE;
        }

        $pluginsPath = ReleaseArchiveBuildRoots::pluginsPath($basePath);

        try {
            $plugins = ReleaseSkeleton::releasePlugins($pluginsPath);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('skip-build')) {
            // Only the package and the published plugins are built.
            $roots = array_values(array_filter(
                ReleaseArchiveBuildRoots::discoverForRelease($basePath),
                fn (string $root): bool => dirname($root) !== rtrim($pluginsPath, '/\\') || in_array(basename($root), $plugins, true),
            ));
            $error = ReleaseAssetBuilder::build($basePath, $roots, fn (string $line) => $this->output->write($line));

            if ($error !== null) {
                $this->error($error);

                return self::FAILURE;
            }
        }

        $stagingPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mksine-release-' . $version . '-' . bin2hex(random_bytes(4));

        try {
            (new ReleaseSkeleton(
                packagePath: $basePath . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'mksine',
                pluginsPath: $pluginsPath,
                stagingPath: $stagingPath,
                composer: $composer,
                version: $version,
                log: fn (string $message) => $this->info($message),
                laravelConstraint: (string) $this->option('laravel'),
                plugins: $plugins,
            ))->build();

            $outputDirectory = $this->outputDirectory($version);
            File::ensureDirectoryExists($outputDirectory);
            $files = [];

            foreach (array_keys(ReleaseManifest::LAYOUTS) as $layout) {
                $path = $outputDirectory . DIRECTORY_SEPARATOR . ReleaseManifest::fileName($version, $layout);
                $this->info("Packing the {$layout} archive…");

                ReleaseArchiveZipper::createArchive($stagingPath, $path, new ReleaseArchiveOptions(
                    includeExtensions: true,
                    layout: $layout,
                    topLevelAllowlist: ReleaseSkeleton::TOP_LEVEL_ALLOWLIST,
                ));

                $files[$layout] = $path;
            }

            $installerPath = QuickInstaller::write($outputDirectory, $version);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if (! $this->option('keep-staging')) {
                File::deleteDirectory($stagingPath);
            } else {
                $this->line("Staging application kept at {$stagingPath}");
            }
        }

        $manifest = ReleaseManifest::build($version, $files, now(), $installerPath);
        File::put($outputDirectory . DIRECTORY_SEPARATOR . ReleaseManifest::FILE_NAME, (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        File::put($outputDirectory . DIRECTORY_SEPARATOR . ReleaseManifest::CHECKSUMS_FILE_NAME, ReleaseManifest::checksums($manifest));

        $this->newLine();
        $this->info("MKSine {$version} release files:");
        foreach ($manifest['files'] as $file) {
            $this->line(sprintf('  %s  %s MB', $file['name'], number_format($file['size'] / 1048576, 1)));
        }
        $this->line('  ' . QuickInstaller::FILE_NAME . ', ' . ReleaseManifest::FILE_NAME . ', ' . ReleaseManifest::CHECKSUMS_FILE_NAME);
        $this->line("  in {$outputDirectory}");

        return self::SUCCESS;
    }

    private function outputDirectory(string $version): string
    {
        $option = $this->option('output-dir');

        if (! is_string($option) || $option === '') {
            return storage_path('app' . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $version);
        }

        return str_starts_with($option, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $option) === 1
            ? $option
            : base_path($option);
    }
}
