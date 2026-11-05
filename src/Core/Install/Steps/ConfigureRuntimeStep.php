<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\Environment\EnvironmentFileEditor;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;

/**
 * Storage link, queue driver and the cron line the site needs.
 */
class ConfigureRuntimeStep extends AbstractInstallStep
{
    public function __construct(private readonly EnvironmentProbe $probe) {}

    public function key(): string
    {
        return 'runtime';
    }

    public function run(InstallContext $context): StepResult
    {
        $messages = [];
        $warnings = [];

        $missingLinks = array_filter(
            array_keys((array) config('filesystems.links', [])),
            fn (string $link): bool => ! file_exists($link),
        );

        if ($missingLinks === []) {
            $messages[] = $this->message('storage_link_exists');
        } elseif ($this->probe->functionAvailable('symlink')) {
            [$code] = $this->artisan('storage:link');

            if ($code === 0) {
                $messages[] = $this->message('storage_link_created');
            } else {
                $warnings[] = $this->message('storage_link_unavailable');
            }
        } else {
            $warnings[] = $this->message('storage_link_unavailable');
        }

        if ($context->isShared() && config('queue.default') !== 'database') {
            EnvironmentFileEditor::openOrCreate(app()->environmentFilePath())->set(['QUEUE_CONNECTION' => 'database']);
            config(['queue.default' => 'database']);
            $messages[] = $this->message('queue_database');
        }

        $messages[] = $this->message('cron_line', ['line' => self::cronLine()]);

        if (! $context->isShared()) {
            $messages[] = $this->message('queue_worker_hint');
        }

        return StepResult::completed($messages, $warnings);
    }

    public static function cronLine(): string
    {
        return '* * * * * ' . self::cliPhpBinary() . ' ' . base_path('artisan') . ' schedule:run >> /dev/null 2>&1';
    }

    /**
     * Under PHP-FPM, PHP_BINARY is the FPM daemon, not the CLI a cron job needs.
     */
    public static function cliPhpBinary(): string
    {
        if (PHP_SAPI === 'cli') {
            return PHP_BINARY;
        }

        $candidate = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';

        return @is_executable($candidate) ? $candidate : 'php';
    }
}
