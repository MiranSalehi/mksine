<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Core\Plugins\PluginManager;
use Throwable;

/**
 * Installs and activates the plugins chosen during installation, then publishes their assets.
 */
class ActivatePluginsStep extends AbstractInstallStep
{
    public function __construct(private readonly PluginManager $plugins) {}

    public function key(): string
    {
        return 'plugins';
    }

    public function run(InstallContext $context): StepResult
    {
        if ($context->plugins === []) {
            return StepResult::skipped($this->message('plugins_none_selected'));
        }

        if (! $this->hasTable('mks_plugins')) {
            return StepResult::skipped($this->message('database_not_ready'));
        }

        $messages = [];
        $warnings = [];

        foreach ($context->plugins as $pluginId) {
            if ($this->plugins->getManifest($pluginId) === null) {
                return StepResult::failed($this->message('plugin_not_found', ['plugin' => $pluginId]));
            }

            if ($this->plugins->getRegistry()->isActive($pluginId)) {
                $messages[] = $this->message('plugin_already_active', ['plugin' => $pluginId]);

                continue;
            }

            try {
                if (! $this->plugins->activate($pluginId)) {
                    return StepResult::failed($this->message('plugin_activation_failed', ['plugin' => $pluginId, 'error' => '']));
                }
            } catch (Throwable $exception) {
                return StepResult::failed($this->message('plugin_activation_failed', ['plugin' => $pluginId, 'error' => $exception->getMessage()]));
            }

            [$code] = $this->artisan('mks-plugin:publish', ['plugin' => $pluginId]);

            if ($code !== 0) {
                $warnings[] = $this->message('command_failed', ['command' => "mks-plugin:publish {$pluginId}"]);
            }

            $messages[] = $this->message('plugin_activated', ['plugin' => $pluginId]);
        }

        return StepResult::completed($messages, $warnings);
    }
}
