<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoCheck;
use Miran\Mksine\Seo\SeoFinding;

abstract class AbstractSeoCheck implements SeoCheck
{
    /**
     * @param  'good'|'ok'|'bad'  $status
     * @param  array<string, string|int>  $replace
     */
    protected function finding(string $status, int $score, array $replace = [], ?string $labelKey = null): SeoFinding
    {
        return new SeoFinding(
            id: $this->id(),
            panel: $this->panel(),
            status: $status,
            labelKey: $labelKey ?? 'mksine::seo.checks.'.$this->id().'.'.$status,
            score: max(0, min(100, $score)),
            replace: $replace,
        );
    }
}
