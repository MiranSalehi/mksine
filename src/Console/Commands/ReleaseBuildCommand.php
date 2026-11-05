<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Support\PackageVersion;
use Miran\Mksine\Support\ReleaseManifest;

/**
 * Builds everything a public release needs: the shared-hosting and server zips,
 * SHA256SUMS and release.json for the download page.
 */
class ReleaseBuildCommand extends Command
{
    protected $signature = 'mks:release-build
                            {--output-dir= : Directory for the release files (default storage/app/releases/{version})}
                            {--no-dev : Production Composer dependencies only (recommended for public releases)}
                            {--skip-build : Skip npm builds}';

    protected $description = 'Build the shared-hosting and server zips, SHA256SUMS and release.json for a public release';

    public function handle(): int
    {
        $version = PackageVersion::current();
        $outputDirectory = $this->outputDirectory($version);
        File::ensureDirectoryExists($outputDirectory);

        $files = [];
        $skipBuild = (bool) $this->option('skip-build');

        foreach (array_keys(ReleaseManifest::LAYOUTS) as $layout) {
            $path = $outputDirectory . DIRECTORY_SEPARATOR . ReleaseManifest::fileName($version, $layout);
            $this->info("Building {$layout} archive…");

            $exitCode = $this->call('mks:release-archive', array_filter([
                '--layout' => $layout,
                '--output' => $path,
                '--no-dev' => (bool) $this->option('no-dev'),
                '--skip-build' => $skipBuild,
            ]));

            if ($exitCode !== self::SUCCESS) {
                $this->error("Building the {$layout} archive failed.");

                return self::FAILURE;
            }

            // Assets are built once; the second layout reuses them.
            $skipBuild = true;
            $files[$layout] = $path;
        }

        $manifest = ReleaseManifest::build($version, $files, now());
        File::put($outputDirectory . DIRECTORY_SEPARATOR . ReleaseManifest::FILE_NAME, (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        File::put($outputDirectory . DIRECTORY_SEPARATOR . ReleaseManifest::CHECKSUMS_FILE_NAME, ReleaseManifest::checksums($manifest));

        $this->newLine();
        $this->info("MKSine {$version} release files:");
        foreach ($manifest['files'] as $file) {
            $this->line(sprintf('  %s  %s MB', $file['name'], number_format($file['size'] / 1048576, 1)));
        }
        $this->line('  ' . ReleaseManifest::FILE_NAME . ', ' . ReleaseManifest::CHECKSUMS_FILE_NAME);
        $this->line("  in {$outputDirectory}");

        if (! $this->option('no-dev')) {
            $this->warn('Built without --no-dev: the zips include development packages. Use --no-dev for public releases.');
        }

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
