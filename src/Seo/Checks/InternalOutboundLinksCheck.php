<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo\Checks;

use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;
use Miran\Mksine\Seo\SeoText;

final class InternalOutboundLinksCheck extends AbstractSeoCheck
{
    public function id(): string
    {
        return 'internal_outbound_links';
    }

    public function panel(): string
    {
        return 'seo';
    }

    public function analyze(SeoContext $context): SeoFinding
    {
        $counts = SeoText::countLinks($context->html);
        $replace = [
            'internal' => $counts['internal'],
            'outbound' => $counts['outbound'],
        ];

        if ($counts['internal'] > 0) {
            return $this->finding('good', 100, $replace);
        }

        if ($counts['outbound'] > 0) {
            return $this->finding('ok', 50, $replace);
        }

        return $this->finding('ok', 40, $replace);
    }
}
