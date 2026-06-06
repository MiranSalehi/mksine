<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

use Illuminate\Support\Facades\File;
use Miran\Mksine\Models\ConsoleProcess;

final class AdminConsoleProcessManager
{
    public function __construct(
        private readonly string $projectRoot,
    ) {}

    public function start(string $command, int $userId): ConsoleProcess
    {
        $parsed = AdminConsoleCommandParser::parse($command, $this->projectRoot);

        File::ensureDirectoryExists(storage_path('app/console-processes'));

        $process = ConsoleProcess::query()->create([
            'user_id' => $userId,
            'runner' => $parsed['runner'],
            'command' => $parsed['display'],
            'argv' => $parsed['argv'],
            'status' => ConsoleProcessStatus::Pending,
            'output_path' => storage_path('app/console-processes/placeholder.log'),
        ]);

        $outputPath = storage_path('app/console-processes/'.$process->id.'.log');
        File::put($outputPath, sprintf(
            "[%s] %s\n%s\n\n",
            now()->format('Y-m-d H:i:s'),
            $parsed['display'],
            str_repeat('─', 48),
        ));

        $process->update(['output_path' => $outputPath]);

        $this->launchDetachedRunner((int) $process->id);

        return $process->fresh() ?? $process;
    }

    public function stop(ConsoleProcess $process): ConsoleProcess
    {
        if ($process->pid !== null && $this->isPidAlive((int) $process->pid)) {
            $this->signalProcess((int) $process->pid, SIGTERM);

            $deadline = microtime(true) + 5;
            while (microtime(true) < $deadline && $this->isPidAlive((int) $process->pid)) {
                usleep(200_000);
            }

            if ($this->isPidAlive((int) $process->pid)) {
                $this->signalProcess((int) $process->pid, SIGKILL);
            }
        }

        if ($process->status->isActive()) {
            $this->appendLine($process, sprintf("\n[%s] Process stopped by admin.\n", now()->format('Y-m-d H:i:s')));
            $process->update([
                'status' => ConsoleProcessStatus::Stopped,
                'pid' => null,
                'finished_at' => now(),
            ]);
        }

        return $process->fresh() ?? $process;
    }

    public function syncStatus(ConsoleProcess $process): ConsoleProcess
    {
        if ($process->status !== ConsoleProcessStatus::Running || $process->pid === null) {
            return $process;
        }

        if ($this->isPidAlive((int) $process->pid)) {
            return $process;
        }

        if ($process->status === ConsoleProcessStatus::Running) {
            $process->update([
                'status' => ConsoleProcessStatus::Completed,
                'pid' => null,
                'finished_at' => $process->finished_at ?? now(),
            ]);
        }

        return $process->fresh() ?? $process;
    }

    public function isPidAlive(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return posix_kill($pid, 0);
        }

        if (! is_dir("/proc/{$pid}")) {
            return false;
        }

        return true;
    }

    public function readOutputFromOffset(ConsoleProcess $process, int $offset): array
    {
        $path = $process->output_path;

        if (! is_file($path)) {
            return ['chunk' => '', 'offset' => $offset];
        }

        clearstatcache(true, $path);
        $size = filesize($path);

        if ($size === false || $size <= $offset) {
            return ['chunk' => '', 'offset' => $offset];
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['chunk' => '', 'offset' => $offset];
        }

        fseek($handle, $offset);
        $chunk = fread($handle, $size - $offset);
        fclose($handle);

        return [
            'chunk' => is_string($chunk) ? $chunk : '',
            'offset' => $size,
        ];
    }

    public function appendLine(ConsoleProcess $process, string $line): void
    {
        if ($line === '') {
            return;
        }

        $handle = fopen($process->output_path, 'ab');
        if ($handle === false) {
            return;
        }

        fwrite($handle, $line);
        fflush($handle);
        fclose($handle);
    }

    private function launchDetachedRunner(int $processId): void
    {
        $php = AdminConsolePhpBinary::path();
        $artisan = $this->projectRoot.DIRECTORY_SEPARATOR.'artisan';

        if (PHP_OS_FAMILY === 'Windows') {
            $command = sprintf(
                'start /B "" %s %s mks:console-run-detached %d',
                $this->escapeShellArgument($php),
                $this->escapeShellArgument($artisan),
                $processId,
            );
            pclose(popen($command, 'r'));

            return;
        }

        $inner = sprintf(
            'exec %s %s mks:console-run-detached %d',
            $this->escapeShellArgument($php),
            $this->escapeShellArgument($artisan),
            $processId,
        );

        $command = sprintf(
            'cd %s && nohup setsid sh -c %s </dev/null >/dev/null 2>&1 &',
            $this->escapeShellArgument($this->projectRoot),
            $this->escapeShellArgument($inner),
        );

        exec($command);
    }

    private function escapeShellArgument(string $argument): string
    {
        return escapeshellarg($argument);
    }

    private function signalProcess(int $pid, int $signal): void
    {
        if (function_exists('posix_kill')) {
            posix_kill($pid, $signal);

            return;
        }

        if ($signal === SIGTERM) {
            exec(sprintf('kill -TERM %d 2>/dev/null', $pid));
        } elseif ($signal === SIGKILL) {
            exec(sprintf('kill -KILL %d 2>/dev/null', $pid));
        }
    }
}
