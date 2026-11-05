<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\Environment\RequirementCheck;
use Miran\Mksine\Core\Install\Environment\RequirementsChecker;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;

class CheckRequirementsStep extends AbstractInstallStep
{
    public function key(): string
    {
        return 'requirements';
    }

    public function run(InstallContext $context): StepResult
    {
        $checks = $this->checker()->check();
        $describe = fn (RequirementCheck $check): string => __('mksine::install.' . $check->labelKey, $check->parameters);

        $blocking = array_values(array_filter($checks, fn (RequirementCheck $check): bool => $check->isBlocking()));
        $warnings = array_values(array_map(
            $describe,
            array_filter($checks, fn (RequirementCheck $check): bool => ! $check->required && ! $check->passed),
        ));

        if ($blocking !== []) {
            return StepResult::failed(
                $this->message('requirements_missing', ['items' => implode(', ', array_map($describe, $blocking))]),
                $warnings,
            );
        }

        return StepResult::completed([$this->message('requirements_ok')], $warnings);
    }

    protected function checker(): RequirementsChecker
    {
        return new RequirementsChecker(
            RequirementsChecker::writablePathsFor(base_path(), storage_path(), public_path()),
        );
    }
}
