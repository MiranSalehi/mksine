<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

final class ConsecutiveLongSentencesCheck extends AbstractSeoCheck
{
    public function id(): string
    {
        return 'consecutive_long_sentences';
    }

    public function panel(): string
    {
        return 'readability';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        $sentences = SeoText::sentences(SeoText::stripHtml($context->html));

        if ($sentences === []) {
            return $this->finding('bad', 10);
        }

        $streak = 0;
        $maxStreak = 0;
        $longCount = 0;

        foreach ($sentences as $sentence) {
            $isLong = SeoText::wordCount($sentence) > 20;

            if ($isLong) {
                $longCount++;
                $streak++;
                $maxStreak = max($maxStreak, $streak);
            } else {
                $streak = 0;
            }
        }

        if ($maxStreak >= 2) {
            return $this->finding('bad', 30, ['count' => $maxStreak]);
        }

        if ($longCount > 0) {
            return $this->finding('ok', 70, ['count' => $longCount]);
        }

        return $this->finding('good', 100);
    }
}
