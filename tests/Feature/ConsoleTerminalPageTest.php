<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Miran\Mksine\Filament\Pages\ConsoleTerminal;
use Miran\Mksine\Models\ConsoleLog;
use Miran\Mksine\Models\ConsoleProcess;
use Miran\Mksine\Support\Console\AdminConsoleProcessManager;
use Miran\Mksine\Support\Console\ConsoleProcessStatus;

require_once __DIR__.'/AdminMenu/AdminMenuTestHelpers.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    adminMenuPanel();
});

function consoleLog(array $attributes = []): ConsoleLog
{
    return ConsoleLog::query()->create(array_merge([
        'user_id' => null,
        'runner' => 'artisan',
        'command' => 'php artisan about',
        'argv' => ['php', 'artisan', 'about'],
        'output' => 'Application Name ..... MKSine',
        'exit_code' => 0,
        'duration_ms' => 1200,
    ], $attributes));
}

test('super admins see the terminal and the run history', function (): void {
    $this->actingAs(adminMenuSuperAdmin());
    consoleLog(['command' => 'php artisan optimize:clear']);

    $this->get(ConsoleTerminal::getUrl())
        ->assertOk()
        ->assertSee('mksAdminConsole', false)
        ->assertSee('optimize:clear')
        ->assertSee('1.2 s');
});

test('non super admins cannot open the console or read its history', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(ConsoleTerminal::getUrl())->assertForbidden();

    expect(fn () => (new ConsoleTerminal)->logEntry(consoleLog()->id))->toThrow(AuthorizationException::class)
        ->and(fn () => (new ConsoleTerminal)->consoleSuggestions())->toThrow(AuthorizationException::class);
});

test('history list does not load stored output until an entry is opened', function (): void {
    $this->actingAs(adminMenuSuperAdmin());
    $log = consoleLog(['output' => "line one\nline two", 'exit_code' => 2]);

    $page = new ConsoleTerminal;

    expect($page->getLogsProperty()->first()->getAttributes())->not->toHaveKey('output')
        ->and($page->logEntry($log->id))->toMatchArray([
            'id' => $log->id,
            'command' => 'php artisan about',
            'output' => "line one\nline two",
            'exit_code' => 2,
            'duration' => '1.2 s',
        ])
        ->and($page->logEntry(999_999))->toBeNull();
});

test('autocomplete offers allowlisted commands only', function (): void {
    $this->actingAs(adminMenuSuperAdmin());
    consoleLog(['command' => 'php artisan migrate:status']);
    consoleLog(['command' => 'php artisan migrate:status']);

    $suggestions = (new ConsoleTerminal)->consoleSuggestions();
    $values = array_column($suggestions['commands'], 'value');

    expect($values)
        ->toContain('php artisan migrate:status', 'php artisan optimize:clear', 'composer dump-autoload')
        ->not->toContain('php artisan tinker', 'php artisan db:seed', 'php artisan mks:console-run-detached', 'php artisan mksine:fresh-super-admin')
        ->and($suggestions['recent'])->toBe(['php artisan migrate:status']);
});

test('quick command chips skip commands the allowlist rejects', function (): void {
    $this->actingAs(adminMenuSuperAdmin());
    config([
        'mksine.console_terminal.quick_commands' => ['php artisan about', 'php artisan tinker'],
        'mksine.console_terminal.daemon_presets' => [['label' => 'queue', 'command' => 'php artisan queue:work']],
    ]);

    expect((new ConsoleTerminal)->getConsoleApiProperty()['quickCommands'])->toBe([
        ['label' => 'about', 'command' => 'php artisan about', 'long_running' => false],
        ['label' => 'queue:work', 'command' => 'php artisan queue:work', 'long_running' => true],
    ]);
});

test('deleting a history entry removes it and notifies the terminal', function (): void {
    $this->actingAs(adminMenuSuperAdmin());
    $log = consoleLog();

    Livewire::test(ConsoleTerminal::class)
        ->call('deleteLog', $log->id)
        ->assertDispatched('mks-console-log-deleted', id: $log->id);

    expect(ConsoleLog::query()->whereKey($log->id)->exists())->toBeFalse();
});

test('clearing history removes every entry', function (): void {
    $this->actingAs(adminMenuSuperAdmin());
    consoleLog();
    consoleLog();

    Livewire::test(ConsoleTerminal::class)
        ->call('deleteAllLogs')
        ->assertDispatched('mks-console-logs-cleared');

    expect(ConsoleLog::query()->count())->toBe(0);
});

test('detached runs are saved to the console history', function (bool $succeeds): void {
    $user = adminMenuSuperAdmin();
    $outputPath = storage_path('framework/testing/console-process-'.uniqid().'.log');
    File::ensureDirectoryExists(dirname($outputPath));
    File::put($outputPath, "❯ php artisan demo\n");

    $process = ConsoleProcess::query()->create([
        'user_id' => $user->id,
        'runner' => 'artisan',
        'command' => 'php artisan demo',
        'argv' => [PHP_BINARY, '-r', $succeeds ? 'echo "hello-console";' : 'echo "boom"; exit(3);'],
        'status' => ConsoleProcessStatus::Pending,
        'output_path' => $outputPath,
    ]);

    $this->artisan('mks:console-run-detached', ['processId' => $process->id])
        ->assertExitCode($succeeds ? 0 : 1);

    $log = ConsoleLog::query()->sole();

    expect($log->command)->toBe('php artisan demo')
        ->and($log->user_id)->toBe($user->id)
        ->and($log->exit_code)->toBe($succeeds ? 0 : 3)
        ->and($log->output)->toContain('php artisan demo', $succeeds ? 'hello-console' : 'boom')
        ->and($process->fresh()->status)->toBe($succeeds ? ConsoleProcessStatus::Completed : ConsoleProcessStatus::Failed);

    File::delete($outputPath);
})->with(['success' => true, 'failure' => false]);

test('durations are formatted for the history list', function (?int $milliseconds, string $expected): void {
    expect(ConsoleTerminal::formatDuration($milliseconds))->toBe($expected);
})->with([
    [null, '0 ms'],
    [850, '850 ms'],
    [1000, '1 s'],
    [12_340, '12.3 s'],
    [185_000, '3m 05s'],
    [3_725_000, '1h 02m'],
]);

function stubConsoleProcess(ConsoleProcessStatus $status, ?int $pid = null): ConsoleProcess
{
    $outputPath = storage_path('framework/testing/console-process-'.uniqid().'.log');
    File::ensureDirectoryExists(dirname($outputPath));
    File::put($outputPath, '');

    return ConsoleProcess::query()->create([
        'runner' => 'artisan',
        'command' => 'php artisan about',
        'argv' => ['php', 'artisan', 'about'],
        'status' => $status,
        'pid' => $pid,
        'output_path' => $outputPath,
    ]);
}

test('a process whose runner never starts is failed instead of hanging in pending', function (): void {
    $manager = app(AdminConsoleProcessManager::class);
    $process = stubConsoleProcess(ConsoleProcessStatus::Pending);

    expect($manager->syncStatus($process)->status)->toBe(ConsoleProcessStatus::Pending);

    $this->travel(AdminConsoleProcessManager::LAUNCH_TIMEOUT_SECONDS + 1)->seconds();
    $synced = $manager->syncStatus($process->fresh());

    expect($synced->status)->toBe(ConsoleProcessStatus::Failed)
        ->and($synced->finished_at)->not->toBeNull()
        ->and(File::get($process->output_path))->toContain('did not start');

    File::delete($process->output_path);
});

test('a running process whose runner died is reported as failed', function (): void {
    $process = stubConsoleProcess(ConsoleProcessStatus::Running, pid: 999_999);

    $synced = app(AdminConsoleProcessManager::class)->syncStatus($process);

    expect($synced->status)->toBe(ConsoleProcessStatus::Failed)
        ->and($synced->pid)->toBeNull()
        ->and(File::get($process->output_path))->toContain('exited unexpectedly');

    File::delete($process->output_path);
});

test('a process stopped before its runner started is never run', function (): void {
    $process = stubConsoleProcess(ConsoleProcessStatus::Pending);
    $process->update(['argv' => [PHP_BINARY, '-r', 'echo "should-not-run";']]);

    app(AdminConsoleProcessManager::class)->stop($process);

    $this->artisan('mks:console-run-detached', ['processId' => $process->id])->assertExitCode(1);

    expect($process->fresh()->status)->toBe(ConsoleProcessStatus::Stopped)
        ->and(File::get($process->output_path))->not->toContain('should-not-run')
        ->and(ConsoleLog::query()->count())->toBe(0);

    File::delete($process->output_path);
});

test('stop uses the current pid even when given a stale model', function (): void {
    $process = stubConsoleProcess(ConsoleProcessStatus::Pending);
    $stale = ConsoleProcess::query()->findOrFail($process->id);

    $process->update(['status' => ConsoleProcessStatus::Running, 'pid' => 999_999]);

    $stopped = app(AdminConsoleProcessManager::class)->stop($stale);

    expect($stopped->status)->toBe(ConsoleProcessStatus::Stopped)
        ->and($stopped->pid)->toBeNull();

    File::delete($process->output_path);
});
