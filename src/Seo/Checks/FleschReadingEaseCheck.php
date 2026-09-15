<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

final class FleschReadingEaseCheck extends AbstractSeoCheck
{
    public function id(): string
    {
        return 'flesch_reading_ease';
    }

    public function panel(): string
    {
        return 'readability';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        $score = SeoText::fleschReadingEase(SeoText::stripHtml($context->html));

        if ($score === null) {
            return $this->finding('bad', 0, ['score' => 0]);
        }

        $rounded = (int) round($score);
        $replace = ['score' => $rounded];

        if ($score >= 60) {
            return $this->finding('good', 100, $replace);
        }

        if ($score >= 50) {
            return $this->finding('ok', 70, $replace);
        }

        return $this->finding('bad', 30, $replace);
    }
}
