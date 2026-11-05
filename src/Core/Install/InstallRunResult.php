<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

final readonly class InstallRunResult
{
    /**
     * @param  array<string, StepResult>  $results  Keyed by step key, in run order.
     */
    public function __construct(public array $results) {}

    public function succeeded(): bool
    {
        return $this->failedStepKey() === null;
    }

    public function failedStepKey(): ?string
    {
        foreach ($this->results as $key => $result) {
            if ($result->status === InstallStepStatus::Failed) {
                return $key;
            }
        }

        return null;
    }
}
