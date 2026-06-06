<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
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

    public function handle(AdminConsoleProcessManager $manager): int
    {
        $processId = (int) $this->argument('processId');
        $record = ConsoleProcess::query()->find($processId);

        if ($record === null) {
            $this->error('Console process not found.');

            return self::FAILURE;
        }

        $record->update([
            'status' => ConsoleProcessStatus::Running,
            'pid' => getmypid(),
            'started_at' => now(),
        ]);

        $manager->appendLine($record, sprintf("[%s] PID %d\n", now()->format('Y-m-d H:i:s'), getmypid()));

        /** @var list<string> $argv */
        $argv = AdminConsoleStreamingArgv::enhance($record->argv);

        try {
            $process = new Process($argv, base_path(), null, null, null);

            if (Process::isPtySupported()) {
                $process->setPty(true);
            }

            $process->start();

            while ($process->isRunning()) {
                $manager->appendLine($record, $process->getIncrementalOutput());
                $manager->appendLine($record, $process->getIncrementalErrorOutput());
                usleep(100_000);
            }

            $manager->appendLine($record, $process->getIncrementalOutput());
            $manager->appendLine($record, $process->getIncrementalErrorOutput());

            $exitCode = (int) $process->getExitCode();
            $status = $exitCode === 0 ? ConsoleProcessStatus::Completed : ConsoleProcessStatus::Failed;

            $record->update([
                'status' => $status,
                'exit_code' => $exitCode,
                'pid' => null,
                'finished_at' => now(),
            ]);

            $manager->appendLine($record, sprintf(
                "\n[%s] Process exited with code %d.\n",
                now()->format('Y-m-d H:i:s'),
                $exitCode,
            ));

            return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            $manager->appendLine($record, "\nError: ".$e->getMessage()."\n");
            $record->update([
                'status' => ConsoleProcessStatus::Failed,
                'exit_code' => 1,
                'pid' => null,
                'finished_at' => now(),
            ]);

            return self::FAILURE;
        }
    }
}
