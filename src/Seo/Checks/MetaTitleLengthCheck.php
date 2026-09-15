<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

final class MetaTitleLengthCheck extends AbstractSeoCheck
{
    public function id(): string
    {
        return 'meta_title_length';
    }

    public function panel(): string
    {
        return 'seo';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        $length = SeoText::characterLength($context->metaTitle !== '' ? $context->metaTitle : $context->title);

        if ($length === 0) {
            return $this->finding('bad', 10, ['length' => 0]);
        }

        if ($length >= 50 && $length <= 60) {
            return $this->finding('good', 100, ['length' => $length]);
        }

        if ($length >= 40 && $length <= 70) {
            return $this->finding('ok', 70, ['length' => $length]);
        }

        return $this->finding('bad', 30, ['length' => $length]);
    }
}
