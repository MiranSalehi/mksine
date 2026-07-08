<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Shortcodes;

use Illuminate\Support\Facades\Cache;
use Miran\Mksine\Core\Hooks\Hooks;

final class ContentRenderer
{
    public const FILTER_BEFORE = 'mksine.content.before_shortcodes';

    public const FILTER_AFTER = 'mksine.content.after_shortcodes';

    public function __construct(
        private readonly ShortcodeProcessor $processor,
        private readonly ShortcodeRegistry $registry,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('mksine.features.shortcodes', true);
    }

    public function render(?string $html, ?ShortcodeContext $context = null): string
    {
        $html ??= '';
        $context ??= ShortcodeContext::make();

        if (! $this->isEnabled() || $html === '') {
            return $html;
        }

        if ($this->shouldUseCache($html)) {
            $key = $this->cacheKey($html, $context);

            return Cache::store($this->cacheStore())->remember(
                $key,
                $this->cacheTtl(),
                fn (): string => $this->renderUncached($html, $context),
            );
        }

        return $this->renderUncached($html, $context);
    }

    private function renderUncached(string $html, ShortcodeContext $context): string
    {
        /** @var string $html */
        $html = Hooks::filter(self::FILTER_BEFORE, $html, $context);

        $html = $this->processor->process($html, $context);

        /** @var string $html */
        $html = Hooks::filter(self::FILTER_AFTER, $html, $context);

        return $html;
    }

    private function shouldUseCache(string $html): bool
    {
        if (! (bool) config('mksine.shortcodes.cache.enabled', true)) {
            return false;
        }

        if (app()->runningUnitTests()) {
            return false;
        }

        return ! $this->registry->contentContainsLivewireTag($html);
    }

    private function cacheKey(string $html, ShortcodeContext $context): string
    {
        $prefix = (string) config('mksine.cache.prefix', 'mks_cms');

        $parts = [
            $html,
            (string) $this->registry->registryVersion(),
            app()->getLocale(),
            \Miran\Mksine\Support\MksDateFormatter::calendar(),
            $context->page?->id,
            $context->page?->updated_at?->timestamp,
            $context->post?->id,
            $context->post?->updated_at?->timestamp,
            $context->category?->id,
            $context->category?->updated_at?->timestamp,
        ];

        return $prefix.':shortcodes:'.hash('sha256', implode('|', array_map(
            static fn (mixed $part): string => (string) $part,
            $parts,
        )));
    }

    private function cacheTtl(): int
    {
        return max(1, (int) config('mksine.shortcodes.cache.ttl', 3600));
    }

    private function cacheStore(): ?string
    {
        $store = config('mksine.shortcodes.cache.store');

        return is_string($store) && $store !== '' ? $store : null;
    }
}
