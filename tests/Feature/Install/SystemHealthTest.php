<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\Health\HealthCheck;
use Miran\Mksine\Core\Install\Health\SystemHealthReport;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Filament\Pages\SystemHealth;
use Miran\Mksine\Support\Console\Health\SchedulerMonitor;

require_once __DIR__ . '/../AdminMenu/AdminMenuTestHelpers.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    $this->originalStoragePath = $this->app->storagePath();
    $this->tmp = sys_get_temp_dir() . '/mksine-health-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->tmp . '/storage');
    $this->app->useStoragePath($this->tmp . '/storage');
    config(['filesystems.links' => []]);
    Cache::forget(SchedulerMonitor::HEARTBEAT_KEY);
    Cache::forget(SystemHealth::BADGE_CACHE_KEY);
});

afterEach(function (): void {
    $this->app->useStoragePath($this->originalStoragePath);
    (new Filesystem)->deleteDirectory($this->tmp);
});

function healthCheck(string $section, string $key): ?HealthCheck
{
    foreach (app(SystemHealthReport::class)->build()[$section] as $check) {
        if ($check->key === $key) {
            return $check;
        }
    }

    return null;
}

it('flags a scheduler that never ran and shows the cron line', function (): void {
    $check = healthCheck('scheduler', 'scheduler');

    expect($check->status)->toBe(HealthCheck::ERROR)
        ->and($check->command)->toContain('schedule:run');
});

it('reports a scheduler that ran recently as healthy', function (): void {
    SchedulerMonitor::recordHeartbeat();

    expect(healthCheck('scheduler', 'scheduler')->status)->toBe(HealthCheck::OK);
});

it('treats the scheduler as the queue worker on shared hosting', function (): void {
    config(['queue.default' => 'database']);
    app(InstallationState::class)->markInstalled(['profile' => 'shared']);

    expect(healthCheck('queue', 'queue_worker')->status)->toBe(HealthCheck::ERROR);

    SchedulerMonitor::recordHeartbeat();

    expect(healthCheck('queue', 'queue_worker')->status)->toBe(HealthCheck::OK);
});

it('warns about the sync queue', function (): void {
    config(['queue.default' => 'sync']);

    expect(healthCheck('queue', 'queue_driver')->status)->toBe(HealthCheck::WARNING)
        ->and(healthCheck('queue', 'queue_worker'))->toBeNull();
});

it('flags debug mode on a production site', function (): void {
    config(['app.debug' => true]);
    $this->app['env'] = 'production';

    expect(healthCheck('security', 'debug')->status)->toBe(HealthCheck::ERROR);

    $this->app['env'] = 'testing';
});

it('lists only failing requirements', function (): void {
    expect(app(SystemHealthReport::class)->build()['requirements'])->toHaveCount(1)
        ->and(healthCheck('requirements', 'requirements')->status)->toBe(HealthCheck::OK);
});

it('flags an application folder inside the web root', function (): void {
    $checks = app(SystemHealthReport::class)->build(dirname(base_path()))['security'];

    expect(collect($checks)->firstWhere('key', 'web_root')?->status)->toBe(HealthCheck::ERROR);
});

it('reports disabled background processes as information, not a problem', function (): void {
    $this->app->instance(EnvironmentProbe::class, new EnvironmentProbe(fn (string $key): string | false => $key === 'disable_functions' ? 'proc_open' : false));

    expect(healthCheck('environment', 'processes')->isProblem())->toBeFalse();
});

it('shows the health page to super admins only', function (): void {
    $this->actingAs(User::factory()->create());
    $this->get(SystemHealth::getUrl())->assertForbidden();

    $this->actingAs(adminMenuSuperAdmin());
    $this->get(SystemHealth::getUrl())
        ->assertOk()
        ->assertSee(__('mksine::system_health.sections.scheduler'))
        ->assertSee(__('mksine::system_health.checks.queue_driver'));
});

it('counts problems for the navigation badge', function (): void {
    expect(SystemHealthReport::problemCount(app(SystemHealthReport::class)->build()))->toBeGreaterThan(0)
        ->and(SystemHealth::getNavigationBadge())->not->toBeNull();
});
