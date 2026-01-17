<?php

declare(strict_types=1);

use Miran\Mksine\Core\Plugins\PluginManifest;
use Miran\Mksine\Core\Plugins\PluginRegistry;

describe('PluginRegistry', function () {
    beforeEach(function () {
        $this->registry = new PluginRegistry;

        // Create a temp plugin directory with manifest
        $this->tempDir = sys_get_temp_dir() . '/mksine-test-plugin-' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'test-plugin',
    'name' => 'Test Plugin',
    'version' => '1.0.0',
    'namespace' => 'TestPlugin',
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);
    });

    afterEach(function () {
        // Cleanup temp directory
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    });

    it('is not loaded initially', function () {
        expect($this->registry->isLoaded())->toBeFalse();
    });

    it('can load manifests', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);

        $this->registry->load(['test-plugin' => $manifest]);

        expect($this->registry->isLoaded())->toBeTrue();
        expect($this->registry->getManifests())->toHaveKey('test-plugin');
    });

    it('can check if plugin is discovered', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        expect($this->registry->isDiscovered('test-plugin'))->toBeTrue();
        expect($this->registry->isDiscovered('non-existent'))->toBeFalse();
    });

    it('can get manifest by plugin id', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        $retrieved = $this->registry->getManifest('test-plugin');

        expect($retrieved)->toBeInstanceOf(PluginManifest::class);
        expect($retrieved->id())->toBe('test-plugin');
    });

    it('returns null for non-existent manifest', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        expect($this->registry->getManifest('non-existent'))->toBeNull();
    });

    it('can get all plugin ids', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        $ids = $this->registry->getAllPluginIds();

        expect($ids)->toContain('test-plugin');
    });

    it('reports not_discovered for unknown plugins', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        expect($this->registry->getStatus('unknown-plugin'))->toBe('not_discovered');
    });

    it('reports not_installed for discovered but not installed plugins', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        // Without database, it should be not_installed
        expect($this->registry->getStatus('test-plugin'))->toBe('not_installed');
    });

    it('can clear registry', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        $this->registry->clear();

        expect($this->registry->isLoaded())->toBeFalse();
        expect($this->registry->getManifests())->toBeEmpty();
    });

    it('can get summary', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        $summary = $this->registry->getSummary();

        expect($summary)->toBeArray();
        expect($summary)->toHaveCount(1);
        expect($summary[0]['id'])->toBe('test-plugin');
        expect($summary[0]['name'])->toBe('Test Plugin');
        expect($summary[0]['version'])->toBe('1.0.0');
    });

    it('returns empty bootable plugins without database', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        $bootable = $this->registry->getBootablePlugins();

        expect($bootable)->toBeEmpty();
    });

    it('can check plugin installation status', function () {
        $manifest = PluginManifest::fromPath($this->tempDir);
        $this->registry->load(['test-plugin' => $manifest]);

        expect($this->registry->isInstalled('test-plugin'))->toBeFalse();
        expect($this->registry->isActive('test-plugin'))->toBeFalse();
    });
});
