<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoText;

final class KeyphraseInMetaTitleCheck extends AbstractKeyphraseCheck
{
    public function id(): string
    {
        return 'keyphrase_in_meta_title';
    }

    protected function matches(SeoContext $context): bool
    {
        $haystack = $context->metaTitle !== '' ? $context->metaTitle : $context->title;

        return SeoText::containsKeyphrase($haystack, $context->focusKeyphrase);
    }
}
