<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

final class HeadingPresenceCheck extends AbstractSeoCheck
{
    public function id(): string
    {
        return 'heading_presence';
    }

    public function panel(): string
    {
        return 'readability';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        if (SeoText::hasHeading($context->html)) {
            return $this->finding('good', 100);
        }

        return $this->finding('bad', 20);
    }
}
