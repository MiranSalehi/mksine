<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Catalog;

use Illuminate\Database\Migrations\Migrator;
use Miran\Mksine\Core\Plugins\PluginDiscovery;
use Miran\Mksine\Core\Theme\ThemeManager;

final class SmartMigrationCatalog
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly PluginDiscovery $pluginDiscovery,
        private readonly ThemeManager $themeManager,
    ) {}

    /**
     * @return list<SmartMigrationEntry>
     */
    public function entries(): array
    {
        $ran = array_flip($this->migrator->getRepository()->getRan());
        $entries = [];

        foreach ($this->migrationFiles() as $name => $meta) {
            $entries[] = new SmartMigrationEntry(
                name: $name,
                path: $meta['path'],
                sourceKey: $meta['sourceKey'],
                sourceLabel: $meta['sourceLabel'],
                executed: isset($ran[$name]),
            );
        }

        usort($entries, fn (SmartMigrationEntry $a, SmartMigrationEntry $b): int => strcmp($a->name, $b->name));

        return $entries;
    }

    /**
     * @return list<SmartMigrationEntry>
     */
    public function executedEntries(): array
    {
        return array_values(array_filter(
            $this->entries(),
            fn (SmartMigrationEntry $entry): bool => $entry->executed,
        ));
    }

    /**
     * @return array<string, string>
     */
    public function searchOptions(string $search, bool $executedOnly = true): array
    {
        $needle = mb_strtolower(trim($search));
        $options = [];

        foreach ($executedOnly ? $this->executedEntries() : $this->entries() as $entry) {
            if ($needle !== '' && ! str_contains(mb_strtolower($entry->displayLabel()), $needle)) {
                continue;
            }

            $options[$entry->name] = $entry->displayLabel();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public function migrationPaths(): array
    {
        return $this->discoverPaths();
    }

    /**
     * @return array<string, array{path: string, sourceKey: string, sourceLabel: string}>
     */
    private function migrationFiles(): array
    {
        $files = [];

        foreach ($this->discoverPaths() as $path) {
            foreach ($this->migrator->getMigrationFiles([$path]) as $name => $file) {
                $source = $this->resolveSource($path);

                $files[$name] = [
                    'path' => $file,
                    'sourceKey' => $source['key'],
                    'sourceLabel' => $source['label'],
                ];
            }
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function discoverPaths(): array
    {
        $paths = [database_path('migrations')];

        foreach ($this->migrator->paths() as $path) {
            $paths[] = $path;
        }

        $packageMigrations = base_path('packages/mksine/database/migrations');
        if (is_dir($packageMigrations)) {
            $paths[] = $packageMigrations;
        }

        foreach ($this->pluginDiscovery->discover() as $manifest) {
            $migrationsPath = $manifest->migrationsPath();

            if ($migrationsPath !== null) {
                $paths[] = $migrationsPath;
            }
        }

        foreach ($this->themeManager->discover() as $theme) {
            $migrationsPath = $theme->path.'/database/migrations';

            if (is_dir($migrationsPath)) {
                $paths[] = $migrationsPath;
            }
        }

        $paths = array_values(array_unique(array_filter($paths, is_dir(...))));

        return $paths;
    }

    /**
     * @return array{key: string, label: string}
     */
    private function resolveSource(string $migrationsPath): array
    {
        $normalized = str_replace('\\', '/', $migrationsPath);

        if (preg_match('#/plugins/([^/]+)/database/migrations#', $normalized, $matches) === 1) {
            return [
                'key' => 'plugin:'.$matches[1],
                'label' => 'plugin:'.$matches[1],
            ];
        }

        if (preg_match('#/themes/([^/]+)/database/migrations#', $normalized, $matches) === 1) {
            return [
                'key' => 'theme:'.$matches[1],
                'label' => 'theme:'.$matches[1],
            ];
        }

        if (str_contains($normalized, '/packages/mksine/database/migrations')) {
            return [
                'key' => 'mksine:core',
                'label' => 'mksine:core',
            ];
        }

        return [
            'key' => 'app',
            'label' => 'app',
        ];
    }
}
