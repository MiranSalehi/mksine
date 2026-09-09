<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater\Support;

final class RuntimeCache
{
    public static function resetOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }
}
