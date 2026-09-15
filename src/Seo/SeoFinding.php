<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo;

final readonly class SeoFinding
{
    /**
     * @param  'seo'|'readability'  $panel
     * @param  'good'|'ok'|'bad'  $status
     * @param  array<string, string|int>  $replace
     */
    public function __construct(
        public string $id,
        public string $panel,
        public string $status,
        public string $labelKey,
        public int $score,
        public array $replace = [],
    ) {}

    public function label(): string
    {
        return (string) __($this->labelKey, $this->replace);
    }
}
