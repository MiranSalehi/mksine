<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\Environment\EnvironmentFileEditor;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;

class ConfigureLocaleStep extends AbstractInstallStep
{
    public function key(): string
    {
        return 'locale';
    }

    public function run(InstallContext $context): StepResult
    {
        if (blank($context->locale)) {
            return StepResult::skipped($this->message('locale_unchanged'));
        }

        $locale = (string) $context->locale;

        if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale) !== 1) {
            return StepResult::failed($this->message('locale_invalid', ['locale' => $locale]));
        }

        EnvironmentFileEditor::openOrCreate(app()->environmentFilePath())->set(['APP_LOCALE' => $locale]);
        config(['app.locale' => $locale]);

        return StepResult::completed([$this->message('locale_set', ['locale' => $locale])]);
    }
}
