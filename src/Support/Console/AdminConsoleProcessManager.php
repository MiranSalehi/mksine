<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

use Illuminate\Support\Facades\File;
use Miran\Mksine\Models\ConsoleProcess;

final class AdminConsoleProcessManager
{
    private readonly string $projectRoot;

    public function __construct(?string $projectRoot = null)
    {
        $this->projectRoot = $projectRoot ?? base_path();
    }

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
            "\e[2m[%s]\e[0m \e[1;35m❯\e[0m \e[1m%s\e[0m\n",
            now()->format('Y-m-d H:i:s'),
            $parsed['display'],
        ));

        $process->update(['output_path' => $outputPath]);

        $this->launchDetachedRunner((int) $process->id, $outputPath);

        return $process->fresh() ?? $process;
    }

    public function stop(ConsoleProcess $process): ConsoleProcess
    {
        // The caller's model may predate the runner writing its PID.
        $process = $process->fresh() ?? $process;

        if ($process->pid !== null && $this->isPidAlive((int) $process->pid)) {
            $this->signalProcess((int) $process->pid, SIGTERM);

            // The runner traps SIGTERM, stops its child (≤3s) and saves the run to history before exiting.
            $deadline = microtime(true) + 8;
            while (microtime(true) < $deadline && $this->isPidAlive((int) $process->pid)) {
                usleep(200_000);
            }

            if ($this->isPidAlive((int) $process->pid)) {
                $this->signalProcess((int) $process->pid, SIGKILL);
            }
        }

        $process = $process->fresh() ?? $process;

        if ($process->status->isActive()) {
            $this->appendLine($process, sprintf("\n\e[33m[%s] Process stopped by admin.\e[0m\n", now()->format('Y-m-d H:i:s')));
            $process->update([
                'status' => ConsoleProcessStatus::Stopped,
                'pid' => null,
                'finished_at' => now(),
            ]);
        }

        return $process->fresh() ?? $process;
    }

    /**
     * Seconds a process may stay `pending` (runner not yet started) before it is treated as a failed launch.
     */
    public const LAUNCH_TIMEOUT_SECONDS = 20;

    public function syncStatus(ConsoleProcess $process): ConsoleProcess
    {
        if ($process->status === ConsoleProcessStatus::Pending) {
            return $this->failIfLaunchTimedOut($process);
        }

        if ($process->status !== ConsoleProcessStatus::Running || $process->pid === null) {
            return $process;
        }

        if ($this->isPidAlive((int) $process->pid)) {
            return $process;
        }

        // The runner always records its own final status before exiting; a dead PID that is
        // still "running" means the runner itself crashed or was killed.
        $process = $process->fresh() ?? $process;

        if ($process->status === ConsoleProcessStatus::Running) {
            $this->appendLine($process, sprintf("\n\e[31m[%s] The console runner exited unexpectedly.\e[0m\n", now()->format('Y-m-d H:i:s')));
            $process->update([
                'status' => ConsoleProcessStatus::Failed,
                'pid' => null,
                'finished_at' => $process->finished_at ?? now(),
            ]);
        }

        return $process->fresh() ?? $process;
    }

    private function failIfLaunchTimedOut(ConsoleProcess $process): ConsoleProcess
    {
        if ($process->created_at === null || $process->created_at->diffInSeconds(now()) < self::LAUNCH_TIMEOUT_SECONDS) {
            return $process;
        }

        $process = $process->fresh() ?? $process;

        if ($process->status !== ConsoleProcessStatus::Pending) {
            return $process;
        }

        $this->appendLine($process, sprintf(
            "\n\e[31m[%s] The background runner did not start within %d seconds. Check that the PHP CLI binary works (MKS_CONSOLE_PHP_BINARY) and see storage/logs.\e[0m\n",
            now()->format('Y-m-d H:i:s'),
            self::LAUNCH_TIMEOUT_SECONDS,
        ));

        $process->update([
            'status' => ConsoleProcessStatus::Failed,
            'exit_code' => 1,
            'finished_at' => now(),
        ]);

        return $process->fresh() ?? $process;
    }

    /**
     * JSON shape the console UI uses for a process (start/status/active endpoints and the health panel).
     *
     * @return array<string, mixed>
     */
    public function present(ConsoleProcess $process): array
    {
        return [
            'id' => $process->id,
            'command' => $process->command,
            'runner' => $process->runner,
            'status' => $process->status->value,
            'status_label' => __("mksine::console_terminal.{$process->status->labelKey()}"),
            'pid' => $process->pid,
            'alive' => $process->status->isActive() && ($process->pid === null || $this->isPidAlive((int) $process->pid)),
            'exit_code' => $process->exit_code,
            'started_at' => $process->started_at?->toIso8601String(),
            'finished_at' => $process->finished_at?->toIso8601String(),
            'stream_url' => route('mksine.console-process.stream', ['process' => $process->id]),
            'output_url' => route('mksine.console-process.output', ['process' => $process->id]),
            'stop_url' => route('mksine.console-process.stop', ['process' => $process->id]),
            'status_url' => route('mksine.console-process.status', ['process' => $process->id]),
        ];
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

    private function launchDetachedRunner(int $processId, string $outputPath): void
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

        // `trap '' HUP` does what nohup would (survive the parent going away) without nohup itself,
        // which fails under launchd-managed PHP-FPM on macOS ("can't detach from console").
        $inner = sprintf(
            "trap '' HUP; exec %s %s mks:console-run-detached %d",
            $this->escapeShellArgument($php),
            $this->escapeShellArgument($artisan),
            $processId,
        );

        // `setsid` (util-linux) moves the runner into its own session so it is not tied to the
        // PHP-FPM worker's process group; it does not exist on macOS, where `&` alone is enough.
        // Only the runner itself is backgrounded (with its own redirects). Backgrounding a whole
        // `cd … && …` list would leave a subshell holding PHP's stdout pipe, so exec() would block
        // until the runner exits — i.e. the web request would hang for as long as a worker runs.
        $command = sprintf(
            'cd %s || exit 1; %ssh -c %s </dev/null >>%s 2>&1 &',
            $this->escapeShellArgument($this->projectRoot),
            self::hasSetsid() ? 'setsid ' : '',
            $this->escapeShellArgument($inner),
            $this->escapeShellArgument($outputPath),
        );

        exec($command);
    }

    private static function hasSetsid(): bool
    {
        static $available = null;

        if ($available === null) {
            $path = trim((string) shell_exec('command -v setsid 2>/dev/null'));
            $available = $path !== '' && is_executable($path);
        }

        return $available;
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
