<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Filament\Pages\ConsoleTerminal;

beforeEach(function (): void {
    $this->originalStoragePath = $this->app->storagePath();
    $this->tmp = sys_get_temp_dir() . '/mksine-shared-runtime-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->tmp . '/storage');
    $this->app->useStoragePath($this->tmp . '/storage');
});

afterEach(function (): void {
    $this->app->useStoragePath($this->originalStoragePath);
    (new Filesystem)->deleteDirectory($this->tmp);
});

function scheduledTaskNames(): array
{
    app()->forgetInstance(Schedule::class);

    return array_map(fn (Event $event): string => (string) $event->description, app(Schedule::class)->events());
}

it('drains the queue from the scheduler on shared-hosting installs', function (): void {
    app(InstallationState::class)->markInstalled(['profile' => 'shared']);

    expect(scheduledTaskNames())->toContain('mksine:shared-hosting-queue-worker');
});

it('does not schedule the queue drain on a dedicated server', function (): void {
    app(InstallationState::class)->markInstalled(['profile' => 'vps']);

    expect(scheduledTaskNames())->not->toContain('mksine:shared-hosting-queue-worker');
});

it('hides the admin console when processes cannot be spawned', function (): void {
    $this->app->instance(EnvironmentProbe::class, new EnvironmentProbe(fn (string $key): string | false => $key === 'disable_functions' ? 'proc_open,exec' : false));

    expect(ConsoleTerminal::canAccess())->toBeFalse()
        ->and(ConsoleTerminal::shouldRegisterNavigation())->toBeFalse();
});
