<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Security;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Keeps an iframe `src` only when it is https and its host is on the embed allowlist
 * (`mksine.content.embed_hosts`), so editors can embed Aparat or YouTube but not arbitrary pages.
 */
final class EmbedHostAttributeSanitizer implements AttributeSanitizerInterface
{
    /**
     * @param  list<string>  $allowedHosts
     */
    public function __construct(private readonly array $allowedHosts) {}

    public function getSupportedElements(): ?array
    {
        return ['iframe'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $parts = parse_url(trim($value));

        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https') {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');

        return in_array($host, $this->allowedHosts, true) ? $value : null;
    }
}
