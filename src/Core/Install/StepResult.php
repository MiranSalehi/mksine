<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

/**
 * Outcome of one install step, with user-facing messages and warnings.
 */
final readonly class StepResult
{
    /**
     * @param  list<string>  $messages
     * @param  list<string>  $warnings
     */
    public function __construct(
        public InstallStepStatus $status,
        public array $messages = [],
        public array $warnings = [],
        public ?string $error = null,
    ) {}

    /**
     * @param  list<string>  $messages
     * @param  list<string>  $warnings
     */
    public static function completed(array $messages = [], array $warnings = []): self
    {
        return new self(InstallStepStatus::Completed, $messages, $warnings);
    }

    /**
     * @param  list<string>  $warnings
     */
    public static function skipped(string $reason, array $warnings = []): self
    {
        return new self(InstallStepStatus::Skipped, [$reason], $warnings);
    }

    /**
     * @param  list<string>  $messages
     */
    public static function incomplete(array $messages = []): self
    {
        return new self(InstallStepStatus::Incomplete, $messages);
    }

    /**
     * @param  list<string>  $warnings
     */
    public static function failed(string $error, array $warnings = []): self
    {
        return new self(InstallStepStatus::Failed, [], $warnings, $error);
    }

    public function isFinished(): bool
    {
        return $this->status->isFinished();
    }

    /**
     * @return array{status: string, messages: list<string>, warnings: list<string>, error: string|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'messages' => $this->messages,
            'warnings' => $this->warnings,
            'error' => $this->error,
        ];
    }
}
