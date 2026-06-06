<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

use InvalidArgumentException;
use Symfony\Component\Process\Process;

final class AdminConsoleCommandRunner
{
    public function __construct(
        private readonly string $projectRoot,
    ) {}

    public function run(string $input): AdminConsoleRunResult
    {
        $parsed = AdminConsoleCommandParser::parse($input, $this->projectRoot);

        $timeout = max(30, (int) config('mksine.console_terminal.timeout_seconds', 300));
        $maxOutput = max(10_000, (int) config('mksine.console_terminal.max_output_bytes', 512_000));

        $started = hrtime(true);

        $process = new Process($parsed['argv'], $this->projectRoot, null, null, $timeout);
        $process->run();

        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();
        $combined = trim($stdout.($stderr !== '' && $stdout !== '' ? PHP_EOL : '').$stderr);

        if (strlen($combined) > $maxOutput) {
            $combined = substr($combined, 0, $maxOutput)
                .PHP_EOL.PHP_EOL
                .'[output truncated at '.number_format($maxOutput).' bytes]';
        }

        if ($combined === '' && ! $process->isSuccessful()) {
            $combined = 'Process exited with code '.$process->getExitCode().' and no output.';
        }

        return new AdminConsoleRunResult(
            runner: $parsed['runner'],
            displayCommand: $parsed['display'],
            argv: $parsed['argv'],
            exitCode: (int) $process->getExitCode(),
            output: $combined,
            durationMs: $durationMs,
        );
    }

    public static function assertAllowed(string $input): void
    {
        try {
            AdminConsoleCommandParser::parse($input, base_path());
        } catch (InvalidArgumentException $e) {
            throw $e;
        }
    }
}
