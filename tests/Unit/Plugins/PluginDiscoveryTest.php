<?php

declare(strict_types=1);

use Miran\Mksine\Core\Plugins\PluginDiscovery;

describe('PluginDiscovery filesystem scan', function () {
    afterEach(function (): void {
        if (isset($this->pluginsDir) && is_dir($this->pluginsDir)) {
            $pluginDir = $this->pluginsDir.'/manual-plugin';
            if (is_dir($pluginDir)) {
                unlink($pluginDir.'/plugin.php');
                rmdir($pluginDir);
            }
            rmdir($this->pluginsDir);
        }

        if (isset($this->cachePath) && is_file($this->cachePath)) {
            unlink($this->cachePath);
        }
    });

    it('discovers manually placed plugin directories', function (): void {
        $this->pluginsDir = sys_get_temp_dir().'/mksine-discovery-'.uniqid();
        mkdir($this->pluginsDir, 0755, true);

        $pluginDir = $this->pluginsDir.'/manual-plugin';
        mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir.'/plugin.php', <<<'PHP'
<?php
return [
    'id' => 'manual-plugin',
    'name' => 'Manual Plugin',
    'version' => '1.0.0',
];
PHP);

        $this->cachePath = sys_get_temp_dir().'/mksine-plugin-cache-'.uniqid().'.php';

        $discovery = new PluginDiscovery(paths: [$this->pluginsDir], cachePath: $this->cachePath);
        $manifests = $discovery->rediscover();

        expect($manifests)->toHaveKey('manual-plugin')
            ->and($manifests['manual-plugin']->name())->toBe('Manual Plugin');
    });

    it('clears stale cache when rediscovering manually added plugins', function (): void {
        $this->pluginsDir = sys_get_temp_dir().'/mksine-discovery-'.uniqid();
        mkdir($this->pluginsDir, 0755, true);

        $this->cachePath = sys_get_temp_dir().'/mksine-plugin-cache-'.uniqid().'.php';
        file_put_contents($this->cachePath, '<?php return [];');

        $pluginDir = $this->pluginsDir.'/cached-plugin';
        mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir.'/plugin.php', <<<'PHP'
<?php
return [
    'id' => 'cached-plugin',
    'name' => 'Cached Plugin',
    'version' => '1.0.0',
];
PHP);

        $discovery = new PluginDiscovery(paths: [$this->pluginsDir], cachePath: $this->cachePath);
        $cached = $discovery->discover(useCache: true);
        $rediscovered = $discovery->rediscover();

        expect($cached)->toBe([])
            ->and($rediscovered)->toHaveKey('cached-plugin');
    });
});
