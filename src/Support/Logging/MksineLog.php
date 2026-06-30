<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Logging;

use Illuminate\Support\Facades\Log;

final class MksineLog
{
    /** @var list<string> */
    private const DEVELOPMENT_ENVIRONMENTS = ['local', 'dev'];

    public static function debug(string $message, array $context = []): void
    {
        if (! self::isVerbose()) {
            return;
        }

        Log::debug($message, $context);
    }

    public static function isVerbose(): bool
    {
        $configured = config('mksine.logging.verbose');

        if ($configured !== null) {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        return in_array((string) config('app.env'), self::DEVELOPMENT_ENVIRONMENTS, true);
    }
}
