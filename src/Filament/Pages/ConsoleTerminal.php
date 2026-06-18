<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Models\ConsoleLog;
use Miran\Mksine\Support\Console\AdminConsoleCommandRunner;
use Miran\Mksine\Support\Console\Interactive\AdminInteractiveCommandResolver;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;

class ConsoleTerminal extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $slug = 'console-terminal';

    protected static ?int $navigationSort = 8;

    protected string $view = 'mksine::filament.pages.console-terminal';

    public string $command = '';

    public string $terminalOutput = '';

    public bool $isRunning = false;

    public ?int $selectedLogId = null;

    public function mount(): void
    {
        $consoleJs = dirname(__DIR__, 3).'/resources/js/admin-console-terminal.js';
        if (file_exists($consoleJs)) {
            FilamentAsset::register([
                Js::make('mksine-console-terminal', $consoleJs),
            ]);
        }

        $this->terminalOutput = __('mksine::console_terminal.welcome');
        $this->selectLatestLog();
    }

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
            'interactive' => [
                'detectUrl' => route('mksine.console-process.interactive.detect'),
                'logUrl' => route('mksine.console-process.interactive.log'),
                'migrateSmartCatalogUrl' => route('mksine.console-process.interactive.migrate-smart.catalog'),
                'migrateSmartAnalyzeUrl' => route('mksine.console-process.interactive.migrate-smart.analyze'),
                'migrateSmartExecuteUrl' => route('mksine.console-process.interactive.migrate-smart.execute'),
            ],
            'labels' => [
                'idle' => __('mksine::console_terminal.status_idle'),
                'running' => __('mksine::console_terminal.status_running_short'),
                'interactiveTitle' => __('mksine::console_terminal.interactive_title'),
                'interactiveModeAll' => __('mksine::console_terminal.interactive_mode_all'),
                'interactiveModeSearch' => __('mksine::console_terminal.interactive_mode_search'),
                'interactiveModeSingle' => __('mksine::console_terminal.interactive_mode_single'),
                'interactiveSearchPlaceholder' => __('mksine::console_terminal.interactive_search_placeholder'),
                'interactiveContinue' => __('mksine::console_terminal.interactive_continue'),
                'interactiveBack' => __('mksine::console_terminal.interactive_back'),
                'interactiveDryRun' => __('mksine::console_terminal.interactive_dry_run'),
                'interactiveExecute' => __('mksine::console_terminal.interactive_execute'),
                'interactiveConfirmProduction' => __('mksine::console_terminal.interactive_confirm_production'),
                'interactiveCancel' => __('mksine::console_terminal.interactive_cancel'),
                'interactiveNoMigrations' => __('mksine::console_terminal.interactive_no_migrations'),
                'interactiveNothingToSync' => __('mksine::console_terminal.interactive_nothing_to_sync'),
            ],
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('mksine::console_terminal.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return AdminSidebarNavigation::group(AdminSidebarNavigation::GROUP_SYSTEM);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return SuperAdminGate::check();
    }

    public static function canAccess(): bool
    {
        return SuperAdminGate::check();
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
     * @return Collection<int, ConsoleLog>
     */
    public function getLogsProperty(): Collection
    {
        return ConsoleLog::query()
            ->with('user')
            ->latest('id')
            ->limit(100)
            ->get();
    }

    public function runCommand(): void
    {
        SuperAdminGate::authorize();

        $command = trim($this->command);
        if ($command === '') {
            Notification::make()
                ->title(__('mksine::console_terminal.empty_command'))
                ->warning()
                ->send();

            return;
        }

        if (AdminInteractiveCommandResolver::isInteractiveInput($command, base_path())) {
            Notification::make()
                ->title(__('mksine::console_terminal.interactive_use_start'))
                ->body(__('mksine::console_terminal.interactive_use_start_body'))
                ->info()
                ->send();

            return;
        }

        $this->isRunning = true;

        try {
            $runner = new AdminConsoleCommandRunner(base_path());
            $result = $runner->run($command);

            $log = ConsoleLog::query()->create([
                'user_id' => Auth::id(),
                'runner' => $result->runner,
                'command' => $result->displayCommand,
                'argv' => $result->argv,
                'output' => $result->output,
                'exit_code' => $result->exitCode,
                'duration_ms' => $result->durationMs,
            ]);

            $this->selectedLogId = (int) $log->id;
            $this->terminalOutput = $this->formatLogOutput($log);
            $this->command = '';

            $notification = Notification::make()
                ->title($result->succeeded()
                    ? __('mksine::console_terminal.run_success')
                    : __('mksine::console_terminal.run_failed'));
            if ($result->succeeded()) {
                $notification->success();
            } else {
                $notification->warning();
            }
            $notification->send();
        } catch (InvalidArgumentException $e) {
            $this->terminalOutput = __('mksine::console_terminal.error_prefix').$e->getMessage();
            Notification::make()
                ->title(__('mksine::console_terminal.invalid_command'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            $this->terminalOutput = __('mksine::console_terminal.error_prefix').$e->getMessage();
            Notification::make()
                ->title(__('mksine::console_terminal.run_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isRunning = false;
        }
    }

    public function runQuick(string $command): void
    {
        $this->command = $command;
        $this->runCommand();
    }

    public function selectLog(int $logId): void
    {
        $log = ConsoleLog::query()->find($logId);
        if ($log === null) {
            return;
        }

        $this->selectedLogId = $logId;
        $this->terminalOutput = $this->formatLogOutput($log);
    }

    public function deleteLog(int $logId): void
    {
        SuperAdminGate::authorize();

        ConsoleLog::query()->whereKey($logId)->delete();

        if ($this->selectedLogId === $logId) {
            $this->selectLatestLog();
        }

        Notification::make()
            ->title(__('mksine::console_terminal.log_deleted'))
            ->success()
            ->send();
    }

    public function deleteAllLogs(): void
    {
        SuperAdminGate::authorize();

        ConsoleLog::query()->delete();
        $this->selectedLogId = null;
        $this->terminalOutput = __('mksine::console_terminal.welcome');

        Notification::make()
            ->title(__('mksine::console_terminal.logs_cleared'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_logs')
                ->label(__('mksine::console_terminal.clear_all_logs'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => SuperAdminGate::check())
                ->action(function (): void {
                    $this->deleteAllLogs();
                }),
        ];
    }

    private function selectLatestLog(): void
    {
        $latest = ConsoleLog::query()->latest('id')->first();
        if ($latest === null) {
            return;
        }

        $this->selectedLogId = (int) $latest->id;
        $this->terminalOutput = $this->formatLogOutput($latest);
    }

    private function formatLogOutput(ConsoleLog $log): string
    {
        $header = sprintf(
            "[%s] %s (exit %d, %d ms)\n%s\n\n",
            $log->created_at?->format('Y-m-d H:i:s') ?? '—',
            $log->command,
            (int) $log->exit_code,
            (int) $log->duration_ms,
            str_repeat('─', 48),
        );

        return $header.((string) ($log->output ?? ''));
    }
}
