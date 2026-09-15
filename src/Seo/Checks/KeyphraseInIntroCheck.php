<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoText;

final class KeyphraseInIntroCheck extends AbstractKeyphraseCheck
{
    public function id(): string
    {
        return 'keyphrase_in_intro';
    }

    protected function matches(SeoContext $context): bool
    {
        return SeoText::containsKeyphrase(SeoText::intro($context->html), $context->focusKeyphrase);
    }
}
