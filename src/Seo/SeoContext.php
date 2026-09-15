<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo;

final readonly class SeoContext
{
    public function __construct(
        public string $title = '',
        public string $slug = '',
        public string $metaTitle = '',
        public string $metaDescription = '',
        public string $html = '',
        public string $locale = 'en',
        public string $focusKeyphrase = '',
    ) {}

    public function isEnglish(): bool
    {
        return str_starts_with(strtolower($this->locale), 'en');
    }
}
