<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo;

interface SeoCheck
{
    public function id(): string;

    /**
     * @return 'seo'|'readability'
     */
    public function panel(): string;

    public function analyze(SeoContext $context): SeoFinding;
}
