<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Models\ConsoleLog;
use Miran\Mksine\Models\ConsoleProcess;
use Miran\Mksine\Support\Console\AdminConsoleProcessManager;
use Miran\Mksine\Support\Console\AdminConsoleStreamingArgv;
use Miran\Mksine\Support\Console\ConsoleProcessStatus;
use Symfony\Component\Process\Process;
use Throwable;

final class ConsoleRunDetachedCommand extends Command
{
    protected $signature = 'mks:console-run-detached {processId : Console process database id}';

    protected $description = 'Run a validated admin-console command and stream output to its log file (internal).';

    protected $hidden = true;

    private bool $stopRequested = false;

    public function handle(AdminConsoleProcessManager $manager): int
    {
        $processId = (int) $this->argument('processId');
        $record = ConsoleProcess::query()->find($processId);

        if ($record === null) {
            $this->error('Console process not found.');

            return self::FAILURE;
        }

        // Stopped (or timed out) before the runner got going: do not start the command anymore.
        if ($record->status !== ConsoleProcessStatus::Pending) {
            return self::FAILURE;
        }

        $record->update([
            'status' => ConsoleProcessStatus::Running,
            'pid' => getmypid(),
            'started_at' => now(),
        ]);

        $manager->appendLine($record, sprintf("\e[2m[%s] PID %d\e[0m\n", now()->format('Y-m-d H:i:s'), getmypid()));

        $this->trap([SIGTERM, SIGINT], function (): void {
            $this->stopRequested = true;
        });

        /** @var list<string> $argv */
        $argv = AdminConsoleStreamingArgv::enhance($record->argv);
        $startedAt = hrtime(true);

        try {
            $process = new Process($argv, base_path(), null, null, null);

            if (Process::isPtySupported()) {
                $process->setPty(true);
            }

            $process->start();

            while ($process->isRunning()) {
                if ($this->stopRequested) {
                    $process->stop(3);

                    break;
                }

                $manager->appendLine($record, $process->getIncrementalOutput());
                $manager->appendLine($record, $process->getIncrementalErrorOutput());
                usleep(100_000);
            }

            $manager->appendLine($record, $process->getIncrementalOutput());
            $manager->appendLine($record, $process->getIncrementalErrorOutput());

            $exitCode = (int) ($process->getExitCode() ?? 1);
            $status = match (true) {
                $this->stopRequested => ConsoleProcessStatus::Stopped,
                $exitCode === 0 => ConsoleProcessStatus::Completed,
                default => ConsoleProcessStatus::Failed,
            };

            $manager->appendLine($record, $this->exitLine($status, $exitCode));
        } catch (Throwable $e) {
            $manager->appendLine($record, "\n\e[31mError: ".$e->getMessage()."\e[0m\n");
            $exitCode = 1;
            $status = ConsoleProcessStatus::Failed;
        }

        $this->recordHistory($record, $exitCode, (int) ((hrtime(true) - $startedAt) / 1_000_000));

        $record->update([
            'status' => $status,
            'exit_code' => $exitCode,
            'pid' => null,
            'finished_at' => now(),
        ]);

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function exitLine(ConsoleProcessStatus $status, int $exitCode): string
    {
        $color = match ($status) {
            ConsoleProcessStatus::Completed => '32',
            ConsoleProcessStatus::Stopped => '33',
            default => '31',
        };

        $message = $status === ConsoleProcessStatus::Stopped
            ? 'Process stopped by admin.'
            : sprintf('Process exited with code %d.', $exitCode);

        return sprintf("\n\e[%sm[%s] %s\e[0m\n", $color, now()->format('Y-m-d H:i:s'), $message);
    }

    /**
     * Saves the finished run to the console history so every command — short or long-running — is reviewable later.
     */
    private function recordHistory(ConsoleProcess $record, int $exitCode, int $durationMs): void
    {
        ConsoleLog::query()->create([
            'user_id' => $record->user_id,
            'runner' => $record->runner,
            'command' => $record->command,
            'argv' => $record->argv,
            'output' => $this->readOutputTail($record->output_path),
            'exit_code' => $exitCode,
            'duration_ms' => $durationMs,
        ]);
    }

    private function readOutputTail(string $path): string
    {
        if (! is_file($path)) {
            return '';
        }

        $maxBytes = max(10_000, (int) config('mksine.console_terminal.max_output_bytes', 512_000));
        $size = (int) filesize($path);

        if ($size <= $maxBytes) {
            return mb_scrub((string) file_get_contents($path), 'UTF-8');
        }

        $tail = mb_scrub((string) file_get_contents($path, false, null, $size - $maxBytes), 'UTF-8');

        return sprintf("\e[2m[… earlier output truncated, last %s bytes kept]\e[0m\n", number_format($maxBytes)).$tail;
    }
}
