<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use Miran\Mksine\Filament\Pages\ConsoleTerminal;
use Miran\Mksine\Support\Console\Health\ConsoleHealthReport;
use Miran\Mksine\Support\Console\Health\QueueWorkerMonitor;
use Miran\Mksine\Support\Console\Health\SchedulerMonitor;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

require_once __DIR__.'/AdminMenu/AdminMenuTestHelpers.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    config(['queue.default' => 'database']);
    Cache::forget(SchedulerMonitor::HEARTBEAT_KEY);
    Cache::forget(SchedulerMonitor::TASK_RUNS_KEY);
    Cache::forget(QueueWorkerMonitor::CACHE_KEY);
    Cache::forget(ConsoleHealthReport::SCHEDULE_CACHE_KEY);
    app()->forgetInstance(QueueWorkerMonitor::class);
});

function healthReport(): array
{
    return app(ConsoleHealthReport::class)->build();
}

test('the scheduler is reported down until schedule:run starts, then healthy, then late', function (): void {
    expect(healthReport()['scheduler'])->toMatchArray(['status' => 'down', 'last_run_at' => null])
        ->and(healthReport()['scheduler']['cron_line'])->toContain('php artisan schedule:run');

    Event::dispatch(new CommandStarting('schedule:run', new ArrayInput([]), new NullOutput));

    expect(healthReport()['scheduler']['status'])->toBe('healthy');

    $this->travel(SchedulerMonitor::HEALTHY_WITHIN_SECONDS + 30)->seconds();
    expect(healthReport()['scheduler']['status'])->toBe('late');

    $this->travel(SchedulerMonitor::LATE_WITHIN_SECONDS)->seconds();
    expect(healthReport()['scheduler']['status'])->toBe('down');
});

test('other artisan commands do not count as a scheduler heartbeat', function (): void {
    Event::dispatch(new CommandStarting('queue:work', new ArrayInput([]), new NullOutput));

    expect(SchedulerMonitor::lastHeartbeat())->toBeNull();
});

test('scheduled tasks come from the CLI scheduler, including console-only plugin schedules', function (): void {
    Process::fake([
        '*schedule:list*' => Process::result(json_encode([
            ['expression' => '*/5 * * * *', 'command' => 'php artisan plugin:sync', 'description' => 'plugin:sync', 'timezone' => 'UTC'],
            ['expression' => '0 3 * * *', 'command' => 'php artisan reports:prune', 'description' => null, 'timezone' => 'UTC'],
        ])),
    ]);

    $event = app(Schedule::class)->command('reports:prune')->dailyAt('03:00');
    SchedulerMonitor::recordTaskRun($event, false);

    $tasks = healthReport()['scheduler']['tasks'];

    expect(array_column($tasks, 'name'))->toBe(['plugin:sync', 'php artisan reports:prune'])
        ->and($tasks[0]['expression'])->toBe('*/5 * * * *')
        ->and($tasks[0]['next_run_human'])->not->toBeNull()
        ->and($tasks[1]['last_succeeded'])->toBeFalse()
        ->and($tasks[1]['last_run_human'])->not->toBeNull();

    Process::assertRanTimes(fn ($process): bool => in_array('schedule:list', (array) $process->command, true), 1);

    healthReport();
    Process::assertRanTimes(fn ($process): bool => in_array('schedule:list', (array) $process->command, true), 1);
});

test('scheduled tasks fall back to the in-process schedule when the CLI is unavailable', function (): void {
    Process::fake(['*' => Process::result(errorOutput: 'boom', exitCode: 1)]);

    $tasks = healthReport()['scheduler']['tasks'];

    expect($tasks)->not->toBeEmpty()
        ->and($tasks[0])->toHaveKeys(['name', 'expression', 'next_run_at', 'next_run_human', 'last_run_human', 'last_succeeded']);
});

test('a looping queue worker shows up as active and disappears when it stops', function (): void {
    Event::dispatch(new Looping('database', 'default'));

    $workers = healthReport()['workers'];

    expect($workers['status'])->toBe('healthy')
        ->and($workers['items'])->toHaveCount(1)
        ->and($workers['items'][0])->toMatchArray([
            'connection' => 'database',
            'queue' => 'default',
            'pid' => getmypid(),
            'state' => 'idle',
            'processed' => 0,
        ]);

    Event::dispatch(new WorkerStopping(0));

    expect(QueueWorkerMonitor::activeWorkers())->toBeEmpty();
});

test('a worker that stops reporting is no longer listed', function (): void {
    app(QueueWorkerMonitor::class)->looping('database', 'default');

    $this->travel(QueueWorkerMonitor::STALE_AFTER_SECONDS + 1)->seconds();

    expect(QueueWorkerMonitor::activeWorkers())->toBeEmpty();
});

test('a worker busy with a long job stays listed past the idle timeout', function (): void {
    $monitor = app(QueueWorkerMonitor::class);
    $monitor->looping('database', 'default');
    $this->travel(5)->seconds();
    $monitor->jobProcessing('App\\Jobs\\SlowReport');

    $this->travel(QueueWorkerMonitor::STALE_AFTER_SECONDS + 60)->seconds();

    expect(QueueWorkerMonitor::activeWorkers())->toHaveCount(1)
        ->and(QueueWorkerMonitor::activeWorkers()[0])->toMatchArray([
            'state' => 'busy',
            'current_job' => 'App\\Jobs\\SlowReport',
        ]);
});

test('jobs processed in a web request (sync queue) do not register a worker', function (): void {
    app(QueueWorkerMonitor::class)->jobProcessing('App\\Jobs\\Anything');
    app(QueueWorkerMonitor::class)->jobFinished();

    expect(QueueWorkerMonitor::activeWorkers())->toBeEmpty();
});

test('waiting jobs without a worker mark the workers as down', function (): void {
    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->subMinutes(5)->getTimestamp(),
        'created_at' => now()->subMinutes(5)->getTimestamp(),
    ]);

    $report = healthReport();

    expect($report['queue'])->toMatchArray(['connection' => 'database', 'pending' => 1, 'failed' => 0, 'sync' => false])
        ->and($report['queue']['oldest_pending_human'])->not->toBeNull()
        ->and($report['workers']['status'])->toBe('down');
});

test('an empty queue without a worker is idle, and the sync driver needs no worker', function (): void {
    expect(healthReport()['workers']['status'])->toBe('idle');

    config(['queue.default' => 'sync']);

    expect(healthReport()['workers']['status'])->toBe('not_needed')
        ->and(healthReport()['queue']['sync'])->toBeTrue();
});

test('the console page exposes the health report to super admins only', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    expect((new ConsoleTerminal)->consoleHealth())->toHaveKeys(['scheduler', 'workers', 'queue', 'processes']);

    $this->actingAs(User::factory()->create());

    expect(fn () => (new ConsoleTerminal)->consoleHealth())->toThrow(AuthorizationException::class);
});
