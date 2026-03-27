<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins\Publishing;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Miran\Mksine\Core\Plugins\PluginManifest;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Applies vendor "install" assets into a plugin tree using JSON recipes under
 * {@see PluginManifest::publishesPath() publishes/}.
 *
 * Each preset is a file: publishes/{preset}.json
 *
 * @phpstan-type PresetConfig array{
 *     vendor_path: string,
 *     config?: array{from: string, to: string},
 *     migrations?: list<string>
 * }
 */
final class PluginVendorPublishRunner
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * @return list<string> Preset names (without .json)
     */
    public function listPresets(PluginManifest $manifest): array
    {
        $dir = $manifest->publishesPath();
        if (! is_dir($dir)) {
            return [];
        }

        $presets = [];
        foreach (glob($dir.'/*.json') ?: [] as $file) {
            $base = basename((string) $file, '.json');
            if ($base !== '' && $base !== 'README') {
                $presets[] = $base;
            }
        }
        sort($presets);

        return $presets;
    }

    public function publish(PluginManifest $manifest, string $preset, SymfonyStyle $io, bool $force): int
    {
        $presetPath = $manifest->publishesPath().'/'.$preset.'.json';

        if (! is_file($presetPath)) {
            $available = $this->listPresets($manifest);
            $hint = $available === [] ? 'No JSON files found in publishes/.' : 'Available presets: '.implode(', ', $available);
            $io->error("Preset file not found: [{$presetPath}]. {$hint}");

            return SymfonyCommand::FAILURE;
        }

        try {
            /** @var PresetConfig $data */
            $data = json_decode($this->filesystem->get($presetPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $io->error("Invalid JSON in preset file [{$presetPath}].");

            return SymfonyCommand::FAILURE;
        }

        if (! is_array($data) || empty($data['vendor_path']) || ! is_string($data['vendor_path'])) {
            $io->error('Preset JSON must include a non-empty string "vendor_path" (path under vendor/, e.g. spatie/laravel-activitylog).');

            return SymfonyCommand::FAILURE;
        }

        $pluginBase = $manifest->basePath();
        $packageBase = $pluginBase.'/vendor/'.trim($data['vendor_path'], '/');

        if (! is_dir($packageBase)) {
            $io->error("Package directory not found at [{$packageBase}]. Run `composer require` inside the plugin directory first.");

            return SymfonyCommand::FAILURE;
        }

        $migrationsDir = $pluginBase.'/database/migrations';
        $this->filesystem->ensureDirectoryExists($migrationsDir);
        $this->filesystem->ensureDirectoryExists($pluginBase.'/config');

        $config = $data['config'] ?? null;
        if (is_array($config) && isset($config['from'], $config['to']) && is_string($config['from']) && is_string($config['to'])) {
            $configSource = $packageBase.'/'.ltrim($config['from'], '/');
            $configDest = $pluginBase.'/'.ltrim($config['to'], '/');
            $configDestDir = dirname($configDest);
            $this->filesystem->ensureDirectoryExists($configDestDir);

            if (! is_file($configSource)) {
                $io->error("Config source not found at [{$configSource}].");

                return SymfonyCommand::FAILURE;
            }

            if (is_file($configDest) && ! $force) {
                $io->warning("Config already exists at [{$configDest}]. Use --force to overwrite.");
            } else {
                $this->filesystem->copy($configSource, $configDest);
                $io->writeln("Published config to [{$configDest}].");
            }
        }

        $migrations = $data['migrations'] ?? [];
        if (! is_array($migrations)) {
            $io->error('Preset "migrations" must be a JSON array of migration base names when present.');

            return SymfonyCommand::FAILURE;
        }

        $start = Carbon::now();
        $index = 0;

        foreach ($migrations as $baseName) {
            if (! is_string($baseName) || $baseName === '') {
                continue;
            }
            $moment = $start->copy()->addSeconds(++$index);

            $vendorMigration = $packageBase.'/database/migrations/'.$baseName.'.php';
            if (! is_file($vendorMigration)) {
                $vendorMigration .= '.stub';
            }

            if (! is_file($vendorMigration)) {
                $io->error("Migration source not found for [{$baseName}] under the package.");

                return SymfonyCommand::FAILURE;
            }

            $targetPath = $this->resolveMigrationTargetPath($migrationsDir, $baseName, $moment);

            if (is_file($targetPath)) {
                $io->writeln("Skipping migration (already present): [{$targetPath}].");

                continue;
            }

            $this->filesystem->copy($vendorMigration, $targetPath);
            $io->writeln("Published migration [{$targetPath}].");
        }

        $io->success(
            "Published vendor assets for preset [{$preset}] into the plugin. Merge config at boot if needed, register third-party service providers, then run `php artisan mks-plugin:migrate {$manifest->id()}`."
        );

        return SymfonyCommand::SUCCESS;
    }

    private function resolveMigrationTargetPath(string $migrationsDir, string $migrationFileName, Carbon $now): string
    {
        $formattedFileName = Str::of($migrationFileName)->snake()->finish('.php')->toString();
        $len = strlen($formattedFileName);

        foreach (glob($migrationsDir.'/*.php') ?: [] as $filename) {
            if (substr($filename, -$len) === $formattedFileName) {
                return $filename;
            }
        }

        $stripped = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migrationFileName) ?? $migrationFileName;
        $formattedFileName = Str::of($stripped)->snake()->finish('.php')->toString();
        $timestamp = $now->format('Y_m_d_His');

        return $migrationsDir.'/'.$timestamp.'_'.$formattedFileName;
    }
}
