<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Shortcodes;

interface ShortcodeHandlerInterface
{
    /**
     * @param  array<string, string>  $attrs
     */
    public function handle(array $attrs, ?string $content, ShortcodeContext $context): string;
}
