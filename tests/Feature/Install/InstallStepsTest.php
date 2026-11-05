<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\InstallStepStatus;
use Miran\Mksine\Core\Install\Steps\ClearCachesStep;
use Miran\Mksine\Core\Install\Steps\GeneratePermissionsStep;

uses(RefreshDatabase::class);

function permissionsStep(bool $panelReady, bool $providerRegistersPlugin, bool $procOpen = true): GeneratePermissionsStep
{
    $probe = new EnvironmentProbe(fn (string $key): string | false => $key === 'disable_functions' ? ($procOpen ? '' : 'proc_open') : false);

    return new class($probe, $panelReady, $providerRegistersPlugin) extends GeneratePermissionsStep
    {
        public function __construct(EnvironmentProbe $probe, private bool $panelReady, private bool $providerRegistersPlugin)
        {
            parent::__construct($probe);
        }

        protected function adminPanelIsReady(): bool
        {
            return $this->panelReady;
        }

        protected function panelProviderRegistersPlugin(): bool
        {
            return $this->providerRegistersPlugin;
        }
    };
}

it('runs shield:generate in a fresh process when the panel provider was patched during this run', function (): void {
    Process::fake();

    $result = permissionsStep(panelReady: false, providerRegistersPlugin: true)->run(new InstallContext);

    expect($result->status)->toBe(InstallStepStatus::Completed);
    Process::assertRan(fn (PendingProcess $process): bool => in_array('shield:generate', (array) $process->command, true)
        && in_array('--panel=admin', (array) $process->command, true));
});

it('reports a failed fresh-process generation', function (): void {
    Process::fake(['*' => Process::result(errorOutput: 'Panel [admin] not found', exitCode: 1)]);

    $result = permissionsStep(panelReady: false, providerRegistersPlugin: true)->run(new InstallContext);

    expect($result->status)->toBe(InstallStepStatus::Failed)
        ->and($result->error)->toContain('Panel [admin] not found');
});

it('skips with a hint when processes cannot be spawned', function (): void {
    Process::fake();

    $result = permissionsStep(panelReady: false, providerRegistersPlugin: true, procOpen: false)->run(new InstallContext);

    expect($result->status)->toBe(InstallStepStatus::Skipped)
        ->and($result->warnings)->not->toBeEmpty();
    Process::assertNothingRan();
});

it('skips when the panel provider does not register MksinePlugin', function (): void {
    Process::fake();

    expect(permissionsStep(panelReady: false, providerRegistersPlugin: false)->run(new InstallContext)->status)->toBe(InstallStepStatus::Skipped);
    Process::assertNothingRan();
});

it('clears caches without touching a database cache store whose table does not exist yet', function (): void {
    config(['cache.default' => 'database', 'cache.stores.database.driver' => 'database', 'cache.stores.database.table' => 'cache']);
    Schema::dropIfExists('cache');

    $result = app(ClearCachesStep::class)->run(new InstallContext);

    expect($result->status)->toBe(InstallStepStatus::Completed)
        ->and($result->warnings)->toBe([]);
});

it('skips filament:optimize-clear outside the console, where its icons command does not exist', function (): void {
    $step = new class extends ClearCachesStep
    {
        /** @var list<string> */
        public array $ran = [];

        protected function artisan(string $command, array $parameters = []): array
        {
            $this->ran[] = $command;

            return [0, ''];
        }
    };

    $runningInConsole = (fn () => $this->isRunningInConsole)->call(app());
    (fn () => $this->isRunningInConsole = false)->call(app());

    try {
        $step->run(new InstallContext);
    } finally {
        (fn () => $this->isRunningInConsole = $runningInConsole)->call(app());
    }

    expect($step->ran)->toContain('optimize:clear')
        ->and($step->ran)->not->toContain('filament:optimize-clear');
});
