<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

final class MetaDescriptionLengthCheck extends AbstractSeoCheck
{
    public function id(): string
    {
        return 'meta_description_length';
    }

    public function panel(): string
    {
        return 'seo';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        $length = SeoText::characterLength($context->metaDescription);

        if ($length === 0) {
            return $this->finding('bad', 10, ['length' => 0]);
        }

        if ($length >= 120 && $length <= 160) {
            return $this->finding('good', 100, ['length' => $length]);
        }

        if ($length >= 80 && $length <= 180) {
            return $this->finding('ok', 70, ['length' => $length]);
        }

        return $this->finding('bad', 30, ['length' => $length]);
    }
}
