<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use BezhanSalleh\FilamentShield\FilamentShieldServiceProvider;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\MksineServiceProvider;

/**
 * Publishes config, migrations, translations, fonts and the Shield/permission files.
 * Without --force, files that already exist are left alone.
 */
class PublishResourcesStep extends AbstractInstallStep
{
    /** @var list<string> */
    public const PACKAGE_TAGS = [
        'mksine-config',
        'mksine-livewire-config',
        'mksine-filament-config',
        'mksine-migrations',
        'mksine-lang',
        'mksine-fonts',
    ];

    public function key(): string
    {
        return 'publish';
    }

    public function run(InstallContext $context): StepResult
    {
        foreach (self::PACKAGE_TAGS as $tag) {
            [$code, $output] = $this->artisan('vendor:publish', [
                '--provider' => MksineServiceProvider::class,
                '--tag' => $tag,
                '--force' => $context->force,
            ]);

            if ($code !== 0) {
                return StepResult::failed($this->message('publish_failed', ['tag' => $tag, 'output' => $output]));
            }
        }

        $warnings = [];

        if (class_exists(FilamentShieldServiceProvider::class)) {
            $this->publishShieldFiles($context->force);
        } else {
            $warnings[] = $this->message('shield_missing');
        }

        return StepResult::completed([$this->message('publish_done')], $warnings);
    }

    private function publishShieldFiles(bool $force): void
    {
        foreach (['filament-shield-config' => 'filament-shield.php', 'permission-config' => 'permission.php'] as $tag => $file) {
            if ($force || ! File::exists(config_path($file))) {
                $this->artisan('vendor:publish', ['--tag' => $tag, '--force' => $force]);
            }
        }

        $this->artisan('vendor:publish', ['--tag' => 'permission-migrations', '--force' => $force]);
    }
}
