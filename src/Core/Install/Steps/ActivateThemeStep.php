<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Core\Theme\ThemeManager;
use Miran\Mksine\Models\Theme;

/**
 * Activates the chosen theme, or the default theme when none is active yet.
 */
class ActivateThemeStep extends AbstractInstallStep
{
    public function __construct(private readonly ThemeManager $themes) {}

    public function key(): string
    {
        return 'theme';
    }

    public function run(InstallContext $context): StepResult
    {
        if (! $this->databaseIsReady()) {
            return StepResult::skipped($this->message('database_not_ready'));
        }

        $identifier = $context->theme;

        if (blank($identifier)) {
            if (Theme::active() !== null) {
                return StepResult::skipped($this->message('theme_already_active'));
            }

            $identifier = $this->themes->getDefault()?->identifier;

            if (blank($identifier)) {
                return StepResult::skipped($this->message('theme_none_found'));
            }
        }

        if ($this->themes->get($identifier) === null) {
            return StepResult::failed($this->message('theme_not_found', ['theme' => $identifier]));
        }

        if (! $this->themes->activate($identifier)) {
            return StepResult::failed($this->message('theme_activation_failed', ['theme' => $identifier]));
        }

        return StepResult::completed([$this->message('theme_activated', ['theme' => $identifier])]);
    }
}
