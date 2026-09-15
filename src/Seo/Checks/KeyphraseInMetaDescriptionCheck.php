<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoText;

final class KeyphraseInMetaDescriptionCheck extends AbstractKeyphraseCheck
{
    public function id(): string
    {
        return 'keyphrase_in_meta_description';
    }

    protected function matches(SeoContext $context): bool
    {
        return SeoText::containsKeyphrase($context->metaDescription, $context->focusKeyphrase);
    }
}
