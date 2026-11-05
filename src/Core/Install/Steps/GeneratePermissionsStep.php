<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use BezhanSalleh\FilamentShield\FilamentShieldServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Support\AdminPanelProviderPatcher;
use Throwable;

/**
 * Runs shield:generate for the admin panel.
 *
 * When this run just registered MksinePlugin on the panel provider, the panel in this process
 * was built without it, so generation runs in a fresh PHP process where that is possible.
 */
class GeneratePermissionsStep extends AbstractInstallStep
{
    public function __construct(private readonly EnvironmentProbe $probe) {}

    public function key(): string
    {
        return 'permissions';
    }

    public function run(InstallContext $context): StepResult
    {
        if (! class_exists(FilamentShieldServiceProvider::class)) {
            return StepResult::skipped($this->message('shield_missing'));
        }

        if (! $this->hasTable('permissions')) {
            return StepResult::skipped($this->message('database_not_ready'));
        }

        if (! $this->adminPanelIsReady()) {
            if ($this->panelProviderRegistersPlugin() && $this->probe->functionAvailable('proc_open')) {
                return $this->generateInFreshProcess();
            }

            return StepResult::skipped($this->message('panel_not_ready'), [$this->message('panel_not_ready_hint')]);
        }

        [$code, $output] = $this->artisan('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--no-interaction' => true,
        ]);

        return $code === 0
            ? StepResult::completed([$this->message('permissions_generated')])
            : StepResult::failed($this->message('command_failed', ['command' => 'shield:generate']) . ' ' . $output);
    }

    protected function generateInFreshProcess(): StepResult
    {
        $result = Process::path(base_path())
            ->timeout(600)
            ->run([PHP_BINARY, base_path('artisan'), 'shield:generate', '--all', '--panel=admin', '--no-interaction']);

        return $result->successful()
            ? StepResult::completed([$this->message('permissions_generated')])
            : StepResult::failed($this->message('command_failed', ['command' => 'shield:generate']) . ' ' . trim($result->errorOutput() ?: $result->output()));
    }

    protected function panelProviderRegistersPlugin(): bool
    {
        $path = AdminPanelProviderPatcher::forApp()->path();

        return File::exists($path) && str_contains(File::get($path), 'MksinePlugin::make()');
    }

    protected function adminPanelIsReady(): bool
    {
        try {
            return Filament::getPanel('admin')->hasPlugin('mksine');
        } catch (Throwable) {
            return false;
        }
    }
}
