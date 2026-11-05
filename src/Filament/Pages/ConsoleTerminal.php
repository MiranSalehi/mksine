<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Livewire\Attributes\Renderless;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Models\ConsoleLog;
use Miran\Mksine\Support\Console\AdminConsoleCommandParser;
use Miran\Mksine\Support\Console\Health\ConsoleHealthReport;
use Symfony\Component\Console\Command\Command;
use Throwable;

class ConsoleTerminal extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $slug = 'console-terminal';

    protected static ?int $navigationSort = 30;

    protected string $view = 'mksine::filament.pages.console-terminal';

    /**
     * Commands that are always offered as one-click chips (in addition to `daemon_presets`).
     *
     * @var list<string>
     */
    private const DEFAULT_QUICK_COMMANDS = [
        'php artisan optimize:clear',
        'php artisan migrate:status',
        'php artisan migrate:smart',
        'php artisan about',
    ];

    /**
     * @return array<string, mixed>
     */
    public function getConsoleApiProperty(): array
    {
        return [
            'startUrl' => route('mksine.console-process.start'),
            'activeUrl' => route('mksine.console-process.active'),
            'csrf' => csrf_token(),
            'pollIntervalMs' => (int) config('mksine.console_terminal.status_poll_interval_ms', 2000),
            'quickCommands' => $this->quickCommands(),
            'healthPollMs' => 10_000,
            'healthCommands' => [
                'scheduleRun' => 'php artisan schedule:run',
                'scheduleWork' => 'php artisan schedule:work',
                'queueWork' => $this->queueWorkCommand(),
                'queueRestart' => 'php artisan queue:restart',
                'queueRetry' => 'php artisan queue:retry all',
                'queueFailed' => 'php artisan queue:failed',
            ],
            'interactive' => [
                'detectUrl' => route('mksine.console-process.interactive.detect'),
                'logUrl' => route('mksine.console-process.interactive.log'),
                'migrateSmartCatalogUrl' => route('mksine.console-process.interactive.migrate-smart.catalog'),
                'migrateSmartAnalyzeUrl' => route('mksine.console-process.interactive.migrate-smart.analyze'),
                'migrateSmartExecuteUrl' => route('mksine.console-process.interactive.migrate-smart.execute'),
            ],
            'labels' => collect([
                'status_idle', 'status_pending', 'status_running', 'status_completed', 'status_stopped', 'status_failed',
                'copied', 'delete_log_confirm', 'viewing_history', 'exit_code', 'detached_notice', 'stop_process_confirm',
                'interactive_title', 'interactive_mode_all', 'interactive_mode_search', 'interactive_mode_single',
                'interactive_search_placeholder', 'interactive_continue', 'interactive_back', 'interactive_dry_run',
                'interactive_execute', 'interactive_confirm_production', 'interactive_cancel', 'interactive_no_migrations',
                'interactive_migration_summary', 'interactive_status_ran', 'interactive_status_pending',
                'interactive_nothing_to_sync',
            ])->mapWithKeys(fn (string $key): array => [$key => __("mksine::console_terminal.{$key}")])->all(),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('mksine::console_terminal.navigation_label');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminSidebarNavigation::toolsGroup();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * The terminal runs commands in child processes, so it is hidden where proc_open is
     * disabled (typical on shared hosting).
     */
    public static function canAccess(): bool
    {
        return SuperAdminGate::check() && app(EnvironmentProbe::class)->functionAvailable('proc_open');
    }

    public function getTitle(): string
    {
        return __('mksine::console_terminal.title');
    }

    public function getSubheading(): ?string
    {
        return __('mksine::console_terminal.subheading');
    }

    /**
     * History list (output column excluded — it is loaded on demand by {@see logEntry()}).
     *
     * @return Collection<int, ConsoleLog>
     */
    public function getLogsProperty(): Collection
    {
        return ConsoleLog::query()
            ->select(['id', 'user_id', 'runner', 'command', 'exit_code', 'duration_ms', 'created_at'])
            ->with('user:id,name')
            ->latest('id')
            ->limit(100)
            ->get();
    }

    /**
     * @return array{id: int, command: string, runner: string, output: string, exit_code: int, duration: string, created_at: string|null, user: string|null}|null
     */
    #[Renderless]
    public function logEntry(int $logId): ?array
    {
        SuperAdminGate::authorize();

        $log = ConsoleLog::query()->with('user:id,name')->find($logId);

        if ($log === null) {
            return null;
        }

        return [
            'id' => (int) $log->id,
            'command' => (string) $log->command,
            'runner' => (string) $log->runner,
            'output' => (string) ($log->output ?? ''),
            'exit_code' => (int) $log->exit_code,
            'duration' => self::formatDuration($log->duration_ms),
            'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
            'user' => $log->user?->name,
        ];
    }

    /**
     * Autocomplete data for the prompt: allowlisted commands with descriptions, plus recently run commands.
     *
     * @return array{commands: list<array{value: string, description: string}>, recent: list<string>}
     */
    #[Renderless]
    public function consoleSuggestions(): array
    {
        SuperAdminGate::authorize();

        $commands = [];

        try {
            /** @var array<string, Command> $artisanCommands */
            $artisanCommands = Artisan::all();
        } catch (Throwable) {
            $artisanCommands = [];
        }

        foreach ($artisanCommands as $name => $command) {
            if ($command->isHidden() || ! AdminConsoleCommandParser::isAllowed('artisan', (string) $name)) {
                continue;
            }

            $commands[] = [
                'value' => 'php artisan '.$name,
                'description' => (string) $command->getDescription(),
            ];
        }

        foreach ((array) config('mksine.console_terminal.allowed_commands.composer', []) as $name) {
            $name = trim((string) $name);

            if ($name === '' || str_contains($name, '*')) {
                continue;
            }

            $commands[] = ['value' => 'composer '.$name, 'description' => ''];
        }

        usort($commands, fn (array $a, array $b): int => strcmp($a['value'], $b['value']));

        $recent = ConsoleLog::query()
            ->latest('id')
            ->limit(300)
            ->pluck('command')
            ->map(fn (mixed $command): string => (string) $command)
            ->unique()
            ->take(50)
            ->values()
            ->all();

        return [
            'commands' => $commands,
            'recent' => $recent,
        ];
    }

    /**
     * Scheduler / queue / worker / background-process snapshot for the health panel (polled by the terminal).
     *
     * @return array<string, mixed>
     */
    #[Renderless]
    public function consoleHealth(): array
    {
        SuperAdminGate::authorize();

        return app(ConsoleHealthReport::class)->build();
    }

    public function deleteLog(int $logId): void
    {
        SuperAdminGate::authorize();

        ConsoleLog::query()->whereKey($logId)->delete();

        $this->dispatch('mks-console-log-deleted', id: $logId);

        Notification::make()
            ->title(__('mksine::console_terminal.log_deleted'))
            ->success()
            ->send();
    }

    public function deleteAllLogs(): void
    {
        SuperAdminGate::authorize();

        ConsoleLog::query()->delete();

        $this->dispatch('mks-console-logs-cleared');

        Notification::make()
            ->title(__('mksine::console_terminal.logs_cleared'))
            ->success()
            ->send();
    }

    public static function formatDuration(?int $milliseconds): string
    {
        $milliseconds = max(0, (int) $milliseconds);

        if ($milliseconds < 1000) {
            return $milliseconds.' ms';
        }

        $seconds = $milliseconds / 1000;

        if ($seconds < 60) {
            return rtrim(rtrim(number_format($seconds, 1, '.', ''), '0'), '.').' s';
        }

        $totalSeconds = (int) floor($seconds);

        if ($totalSeconds < 3600) {
            return sprintf('%dm %02ds', intdiv($totalSeconds, 60), $totalSeconds % 60);
        }

        return sprintf('%dh %02dm', intdiv($totalSeconds, 3600), intdiv($totalSeconds % 3600, 60));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_logs')
                ->label(__('mksine::console_terminal.clear_all_logs'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => SuperAdminGate::check() && ConsoleLog::query()->exists())
                ->action(function (): void {
                    $this->deleteAllLogs();
                }),
        ];
    }

    private function queueWorkCommand(): string
    {
        foreach ((array) config('mksine.console_terminal.daemon_presets', []) as $preset) {
            $command = is_array($preset) ? (string) ($preset['command'] ?? '') : '';

            if (preg_match('/\bqueue:work\b/', $command) === 1 && ! str_contains($command, '--once')) {
                return $command;
            }
        }

        return 'php artisan queue:work --tries=3';
    }

    /**
     * One-click chips shown above the prompt; entries the allowlist rejects are dropped.
     *
     * @return list<array{label: string, command: string, long_running: bool}>
     */
    private function quickCommands(): array
    {
        $configured = config('mksine.console_terminal.quick_commands');
        $quick = is_array($configured) ? $configured : self::DEFAULT_QUICK_COMMANDS;

        $entries = [];

        foreach ($quick as $command) {
            $entries[] = ['command' => (string) $command, 'long_running' => false];
        }

        foreach ((array) config('mksine.console_terminal.daemon_presets', []) as $preset) {
            if (is_array($preset) && isset($preset['command'])) {
                $entries[] = ['command' => (string) $preset['command'], 'long_running' => true];
            }
        }

        $chips = [];

        foreach ($entries as $entry) {
            $command = trim($entry['command']);

            if ($command === '' || isset($chips[$command])) {
                continue;
            }

            try {
                AdminConsoleCommandParser::parse($command, base_path());
            } catch (InvalidArgumentException) {
                continue;
            }

            $chips[$command] = [
                'label' => (string) preg_replace('/^(?:php\s+)?artisan\s+/i', '', $command),
                'command' => $command,
                'long_running' => $entry['long_running'],
            ];
        }

        return array_values($chips);
    }
}
