<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

final class ContentWordCountCheck extends AbstractSeoCheck
{
    public function id(): string
    {
        return 'content_word_count';
    }

    public function panel(): string
    {
        return 'seo';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        $count = SeoText::wordCount(SeoText::stripHtml($context->html));

        if ($count === 0) {
            return $this->finding('bad', 0, ['count' => 0]);
        }

        if ($count >= 300) {
            return $this->finding('good', 100, ['count' => $count]);
        }

        if ($count >= 50) {
            return $this->finding('ok', 60, ['count' => $count]);
        }

        return $this->finding('bad', 20, ['count' => $count]);
    }
}
