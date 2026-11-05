<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Throwable;

/**
 * Copies panel and theme assets into the public path (copies, not symlinks).
 */
class PublishAssetsStep extends AbstractInstallStep
{
    /** @var list<string> */
    public const COMMANDS = ['filament:assets', 'mks:theme-publish'];

    public function key(): string
    {
        return 'assets';
    }

    public function run(InstallContext $context): StepResult
    {
        $warnings = [];

        foreach (self::COMMANDS as $command) {
            try {
                [$code, $output] = $this->artisan($command);

                if ($code !== 0) {
                    $warnings[] = $this->message('command_failed', ['command' => $command]) . ' ' . $output;
                }
            } catch (Throwable $exception) {
                $warnings[] = $this->message('command_failed', ['command' => $command]) . ' ' . $exception->getMessage();
            }
        }

        return StepResult::completed([$this->message('assets_published', ['path' => public_path()])], $warnings);
    }
}
