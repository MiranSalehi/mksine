<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

abstract class AbstractKeyphraseCheck extends AbstractSeoCheck
{
    public function panel(): string
    {
        return 'seo';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        if (SeoText::normalize($context->focusKeyphrase) === '') {
            return $this->finding('bad', 0, labelKey: 'mksine::seo.checks.keyphrase_missing');
        }

        if ($this->matches($context)) {
            return $this->finding('good', 100);
        }

        return $this->finding('bad', 20);
    }

    abstract protected function matches(SeoContext $context): bool;
}
