<?php

declare(strict_types=1);

use Miran\Mksine\Core\Plugins\PluginDiscovery;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Services\DiscoveryService;

function deleteDiscoverProbeTree(?string $dir): void
{
    if ($dir === null || ! is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($dir);
}

describe('mks:discover plugin listener paths', function () {
    afterEach(function (): void {
        deleteDiscoverProbeTree($this->probeRoot ?? null);

        if (isset($this->probeCache) && is_string($this->probeCache) && is_file($this->probeCache)) {
            unlink($this->probeCache);
        }
    });

    it('scans a plugin listeners directory and still skips a bad discovery path', function (): void {
        $this->probeRoot = sys_get_temp_dir().'/mksine-discover-plugins-'.uniqid();
        $withListeners = $this->probeRoot.'/with-listeners';
        $withoutListeners = $this->probeRoot.'/without-listeners';

        mkdir($withListeners.'/src/Hooks/Listeners', 0755, true);
        mkdir($withoutListeners, 0755, true);
        file_put_contents($withListeners.'/src/Hooks/Listeners/.gitkeep', '');

        foreach (['with-listeners' => $withListeners, 'without-listeners' => $withoutListeners] as $id => $dir) {
            file_put_contents($dir.'/plugin.php', <<<PHP
<?php
return [
    'id' => '{$id}',
    'name' => '{$id}',
    'version' => '1.0.0',
];
PHP);
        }

        $this->probeCache = sys_get_temp_dir().'/mksine-discover-cache-'.uniqid().'.php';
        $manager = new PluginManager(
            new PluginDiscovery(paths: [$this->probeRoot], cachePath: $this->probeCache),
        );

        app()->instance(PluginManager::class, $manager);

        $scanned = [];
        $this->mock(DiscoveryService::class, function ($mock) use (&$scanned): void {
            $mock->shouldReceive('discoverAll')->andReturnUsing(function (string $path) use (&$scanned): array {
                $scanned[] = $path;

                return [
                    'listeners' => [],
                    'form_hooks' => [],
                    'form_slot_hooks' => [],
                    'table_hooks' => [],
                ];
            });
        });

        $missing = '/tmp/mksine-missing-discovery-'.uniqid();
        config(['mksine.hooks.discovery_paths' => [$missing]]);

        $listeners = realpath($withListeners.'/src/Hooks/Listeners');
        $absent = $withoutListeners.'/src/Hooks/Listeners';

        $this->artisan('mks:discover')
            ->expectsOutputToContain('Scanning: '.$listeners)
            ->expectsOutputToContain('Skipping missing or invalid discovery path: '.$missing)
            ->doesntExpectOutputToContain($absent)
            ->assertSuccessful();

        expect($scanned)->toContain($listeners)
            ->and($scanned)->not->toContain($absent)
            ->and($manager->isInitialized())->toBeFalse();
    });
});
