<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\PanelRegistry;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Plugins\PluginDiscovery;
use Miran\Mksine\Core\Plugins\PluginManager;

/**
 * @return array{root: string, cache: string, manager: PluginManager}
 */
function probePluginManager(string $id, string $providerClass, string $srcRelativeFile, string $srcContents): array
{
    $root = sys_get_temp_dir().'/mksine-panel-provider-'.uniqid();
    $pluginDir = $root.'/'.$id;
    $srcDir = $pluginDir.'/'.dirname($srcRelativeFile);

    mkdir($srcDir, 0755, true);

    $namespace = str_replace('/', '\\', dirname(str_replace('\\', '/', $providerClass)));
    $manifest = <<<PHP
<?php
return [
    'id' => '{$id}',
    'name' => 'Probe',
    'version' => '1.0.0',
    'namespace' => '{$namespace}',
    'autoload' => [
        '{$namespace}\\\\' => 'src/',
    ],
    'filament_panel_provider' => '{$providerClass}',
];
PHP;

    file_put_contents($pluginDir.'/plugin.php', $manifest);
    file_put_contents($pluginDir.'/'.$srcRelativeFile, $srcContents);

    $cache = sys_get_temp_dir().'/mksine-panel-provider-cache-'.uniqid().'.php';
    $manager = new PluginManager(
        new PluginDiscovery(paths: [$root], cachePath: $cache),
    );

    return ['root' => $root, 'cache' => $cache, 'manager' => $manager];
}

function deleteProbeTree(?string $dir): void
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

describe('plugin Filament panel providers', function () {
    beforeEach(function (): void {
        $this->probeRoot = null;
        $this->probeCache = null;
        $this->warnings = [];

        Log::listen(function (MessageLogged $event): void {
            if ($event->level === 'warning') {
                $this->warnings[] = $event->message;
            }
        });
    });

    afterEach(function (): void {
        deleteProbeTree($this->probeRoot);

        if (is_string($this->probeCache) && is_file($this->probeCache)) {
            unlink($this->probeCache);
        }

        $registry = app(PanelRegistry::class);
        unset($registry->panels['mksine-probe-panel'], $registry->panels['mksine-probe-boom']);
        app()->forgetInstance('mksine.probe-panel.registered');
    });

    it('registers the provider and the panel before plugins boot', function (): void {
        $class = 'MksinePanelProbe\\ProbePanelProvider';
        $built = probePluginManager(
            'probe-panel',
            $class,
            'src/ProbePanelProvider.php',
            <<<'PHP'
<?php

namespace MksinePanelProbe;

use Filament\Panel;
use Filament\PanelProvider;

class ProbePanelProvider extends PanelProvider
{
    public function register(): void
    {
        app()->instance('mksine.probe-panel.registered', true);
        parent::register();
    }

    public function panel(Panel $panel): Panel
    {
        return $panel->id('mksine-probe-panel')->path('mksine-probe-panel');
    }
}
PHP,
        );

        $this->probeRoot = $built['root'];
        $this->probeCache = $built['cache'];
        $manager = $built['manager'];

        $manager->registerPluginAutoload();
        $manager->registerDiscoveredFilamentPanelProviders(app());

        $registration = new ReflectionMethod(PluginManager::class, 'registerDiscoveredFilamentPanelProviders');
        $body = implode('', array_slice(
            file($registration->getFileName()),
            $registration->getStartLine() - 1,
            $registration->getEndLine() - $registration->getStartLine() + 1,
        ));

        expect($manager->isInitialized())->toBeFalse()
            ->and($manager->getRegistry()->isLoaded())->toBeFalse()
            ->and($body)->not->toContain('->initialize(')
            ->and($body)->not->toContain('->isActive(')
            ->and(app()->bound('mksine.probe-panel.registered'))->toBeTrue();

        // PanelRegistry is already resolved in this booted app. Forget it so the
        // resolving callback queued by the provider runs, the same point Filament
        // reaches in its boot — still without bootPlugins().
        app()->forgetInstance(PanelRegistry::class);

        expect(Filament::getPanels())->toHaveKey('mksine-probe-panel');
    });

    it('skips a missing panel provider class without crashing', function (): void {
        $built = probePluginManager(
            'probe-missing',
            'MksinePanelProbeMissing\\MissingPanelProvider',
            'src/Unused.php',
            <<<'PHP'
<?php

namespace MksinePanelProbeMissing;

class Unused
{
}
PHP,
        );

        $this->probeRoot = $built['root'];
        $this->probeCache = $built['cache'];

        $built['manager']->registerPluginAutoload();
        $built['manager']->registerDiscoveredFilamentPanelProviders(app());

        expect($built['manager']->isInitialized())->toBeFalse()
            ->and(app()->getProvider('MksinePanelProbeMissing\\MissingPanelProvider'))->toBeNull()
            ->and($this->warnings)->toContain('Plugin Filament panel provider class does not exist.');

        app()->forgetInstance(PanelRegistry::class);

        expect(Filament::getPanels())->not->toHaveKey('mksine-probe-panel');
    });

    it('skips a class that is not a panel provider without crashing', function (): void {
        $class = 'MksinePanelProbePlain\\NotAPanelProvider';
        $built = probePluginManager(
            'probe-plain',
            $class,
            'src/NotAPanelProvider.php',
            <<<'PHP'
<?php

namespace MksinePanelProbePlain;

class NotAPanelProvider
{
}
PHP,
        );

        $this->probeRoot = $built['root'];
        $this->probeCache = $built['cache'];

        $built['manager']->registerPluginAutoload();
        $built['manager']->registerDiscoveredFilamentPanelProviders(app());

        expect($built['manager']->isInitialized())->toBeFalse()
            ->and(app()->getProvider($class))->toBeNull()
            ->and($this->warnings)->toContain('Plugin Filament panel provider must extend '.\Filament\PanelProvider::class.'.');
    });

    it('logs a provider that throws from register and does not register its panel', function (): void {
        $class = 'MksinePanelProbeBoom\\BoomPanelProvider';
        $built = probePluginManager(
            'probe-boom',
            $class,
            'src/BoomPanelProvider.php',
            <<<'PHP'
<?php

namespace MksinePanelProbeBoom;

use Filament\Panel;
use Filament\PanelProvider;

class BoomPanelProvider extends PanelProvider
{
    public function register(): void
    {
        throw new RuntimeException('probe panel register failed');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel->id('mksine-probe-boom')->path('mksine-probe-boom');
    }
}
PHP,
        );

        $this->probeRoot = $built['root'];
        $this->probeCache = $built['cache'];

        $built['manager']->registerPluginAutoload();
        $built['manager']->registerDiscoveredFilamentPanelProviders(app());

        expect($built['manager']->isInitialized())->toBeFalse()
            ->and(app()->getProvider($class))->toBeNull()
            ->and($this->warnings)->toContain('Plugin Filament panel provider failed to register.');

        app()->forgetInstance(PanelRegistry::class);

        expect(Filament::getPanels())->not->toHaveKey('mksine-probe-boom');
    });
});
