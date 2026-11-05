<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Environment;

/**
 * One server requirement. Labels are translation keys so the pre-boot web installer,
 * which has no Laravel translator yet, can render them with its own strings.
 */
final readonly class RequirementCheck
{
    /**
     * @param  array<string, string>  $parameters  Placeholders for the label, e.g. ['name' => 'zip'].
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public bool $passed,
        public bool $required = true,
        public ?string $current = null,
        public array $parameters = [],
    ) {}

    public function isBlocking(): bool
    {
        return $this->required && ! $this->passed;
    }
}
