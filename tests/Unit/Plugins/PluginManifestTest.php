<?php

declare(strict_types=1);

use Miran\Mksine\Core\Plugins\PluginManifest;

describe('PluginManifest', function () {
    beforeEach(function () {
        // Create a temp plugin directory with manifest
        $this->tempDir = sys_get_temp_dir() . '/mksine-test-plugin-' . uniqid();
        mkdir($this->tempDir, 0755, true);
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

    it('can be created from manifest file', function () {
        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'test-plugin',
    'name' => 'Test Plugin',
    'version' => '1.0.0',
    'description' => 'A test plugin',
    'author' => 'Test Author',
    'namespace' => 'TestPlugin',
    'plugin_class' => 'TestPlugin\\TestPluginPlugin',
    'autoload' => [
        'TestPlugin\\' => 'src/',
    ],
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        $manifest = PluginManifest::fromPath($this->tempDir);

        expect($manifest->id())->toBe('test-plugin');
        expect($manifest->name())->toBe('Test Plugin');
        expect($manifest->version())->toBe('1.0.0');
        expect($manifest->description())->toBe('A test plugin');
        expect($manifest->author())->toBe('Test Author');
        expect($manifest->namespace())->toBe('TestPlugin');
        expect($manifest->pluginClass())->toBe('TestPlugin\\TestPluginPlugin');
        expect($manifest->basePath())->toBe($this->tempDir);
    });

    it('provides default values for optional fields', function () {
        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'minimal-plugin',
    'name' => 'Minimal Plugin',
    'version' => '0.1.0',
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        $manifest = PluginManifest::fromPath($this->tempDir);

        expect($manifest->description())->toBeNull();
        expect($manifest->author())->toBeNull();
        expect($manifest->namespace())->toBeNull();
    });

    it('throws exception when manifest file is missing', function () {
        expect(fn () => PluginManifest::fromPath($this->tempDir))
            ->toThrow(InvalidArgumentException::class, 'Plugin manifest not found');
    });

    it('throws exception when manifest does not return array', function () {
        $manifestContent = <<<'PHP'
<?php
return "invalid";
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        expect(fn () => PluginManifest::fromPath($this->tempDir))
            ->toThrow(InvalidArgumentException::class, 'must return an array');
    });

    it('throws exception when required field is missing', function () {
        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'test-plugin',
    // Missing 'name' and 'version'
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        expect(fn () => PluginManifest::fromPath($this->tempDir))
            ->toThrow(InvalidArgumentException::class, 'missing required field');
    });

    it('throws exception for invalid plugin id format', function () {
        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'Invalid_Plugin_Name',
    'name' => 'Test Plugin',
    'version' => '1.0.0',
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        expect(fn () => PluginManifest::fromPath($this->tempDir))
            ->toThrow(InvalidArgumentException::class, 'lowercase alphanumeric');
    });

    it('can get autoload configuration', function () {
        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'test-plugin',
    'name' => 'Test Plugin',
    'version' => '1.0.0',
    'autoload' => [
        'TestPlugin\\' => 'src/',
        'TestPlugin\\Models\\' => 'src/Models/',
    ],
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        $manifest = PluginManifest::fromPath($this->tempDir);
        $autoload = $manifest->autoload();

        expect($autoload)->toHaveKey('TestPlugin\\');
        expect($autoload['TestPlugin\\'])->toBe('src/');
    });

    it('can get requirements', function () {
        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'test-plugin',
    'name' => 'Test Plugin',
    'version' => '1.0.0',
    'requires' => [
        'mksine' => '^1.0',
        'other-plugin' => '>=2.0',
    ],
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        $manifest = PluginManifest::fromPath($this->tempDir);
        $requires = $manifest->requires();

        expect($requires)->toHaveKey('mksine');
        expect($requires['mksine'])->toBe('^1.0');
    });

    it('can get public and private hooks', function () {
        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'test-plugin',
    'name' => 'Test Plugin',
    'version' => '1.0.0',
    'hooks' => [
        'public' => ['test-plugin.event.created'],
        'private' => ['test-plugin.internal.process'],
    ],
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        $manifest = PluginManifest::fromPath($this->tempDir);

        expect($manifest->publicHooks())->toContain('test-plugin.event.created');
        expect($manifest->privateHooks())->toContain('test-plugin.internal.process');
    });

    it('can convert to array', function () {
        $manifestContent = <<<'PHP'
<?php
return [
    'id' => 'test-plugin',
    'name' => 'Test Plugin',
    'version' => '1.0.0',
];
PHP;
        file_put_contents($this->tempDir . '/plugin.php', $manifestContent);

        $manifest = PluginManifest::fromPath($this->tempDir);
        $array = $manifest->toArray();

        expect($array)->toHaveKey('id');
        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('version');
        expect($array['id'])->toBe('test-plugin');
    });

    it('returns null for non-existent paths', function () {
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

        $manifest = PluginManifest::fromPath($this->tempDir);

        expect($manifest->migrationsPath())->toBeNull();
        expect($manifest->configPath())->toBeNull();
        expect($manifest->viewsPath())->toBeNull();
        expect($manifest->filamentResourcesPath())->toBeNull();
    });

    it('can get filament namespaces', function () {
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

        $manifest = PluginManifest::fromPath($this->tempDir);

        expect($manifest->filamentResourcesNamespace())->toBe('TestPlugin\\Filament\\Resources');
        expect($manifest->filamentPagesNamespace())->toBe('TestPlugin\\Filament\\Pages');
        expect($manifest->filamentWidgetsNamespace())->toBe('TestPlugin\\Filament\\Widgets');
    });
});
