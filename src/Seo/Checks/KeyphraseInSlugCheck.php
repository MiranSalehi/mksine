<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoText;

final class KeyphraseInSlugCheck extends AbstractKeyphraseCheck
{
    public function id(): string
    {
        return 'keyphrase_in_slug';
    }

    protected function matches(SeoContext $context): bool
    {
        return SeoText::slugContainsKeyphrase($context->slug, $context->focusKeyphrase);
    }
}
