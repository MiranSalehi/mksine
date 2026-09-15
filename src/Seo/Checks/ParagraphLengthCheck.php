<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

final class ParagraphLengthCheck extends AbstractSeoCheck
{
    public function id(): string
    {
        return 'paragraph_length';
    }

    public function panel(): string
    {
        return 'readability';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        $paragraphs = SeoText::paragraphs($context->html);

        if ($paragraphs === []) {
            return $this->finding('bad', 10);
        }

        $longest = 0;

        foreach ($paragraphs as $paragraph) {
            $longest = max($longest, SeoText::wordCount($paragraph));
        }

        if ($longest > 150) {
            return $this->finding('bad', 30, ['count' => $longest]);
        }

        if ($longest > 100) {
            return $this->finding('ok', 70, ['count' => $longest]);
        }

        return $this->finding('good', 100, ['count' => $longest]);
    }
}
