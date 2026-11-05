<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Support\AdminPanelProviderPatcher;
use Miran\Mksine\Support\WelcomeRoutePatcher;

/**
 * Registers MksinePlugin on the admin panel and removes the stock welcome route, the two
 * manual steps the README used to require after `composer require`.
 */
class ConfigureAdminPanelStep extends AbstractInstallStep
{
    public function key(): string
    {
        return 'panel';
    }

    public function run(InstallContext $context): StepResult
    {
        $messages = [];
        $warnings = [];

        match (AdminPanelProviderPatcher::forApp()->patch()) {
            AdminPanelProviderPatcher::RESULT_PATCHED => $warnings[] = $this->message('panel_patched'),
            AdminPanelProviderPatcher::RESULT_ALREADY => $messages[] = $this->message('panel_ready'),
            AdminPanelProviderPatcher::RESULT_MISSING => $warnings[] = $this->message('panel_missing'),
            default => $warnings[] = $this->message('panel_not_ready_hint'),
        };

        if (WelcomeRoutePatcher::forApp()->patch() === WelcomeRoutePatcher::RESULT_PATCHED) {
            $messages[] = $this->message('welcome_route_removed');
        }

        return StepResult::completed($messages, $warnings);
    }
}
