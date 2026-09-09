<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use Illuminate\Support\Facades\Artisan;

/**
 * Artisan::call() returns an exit code and does not throw on command FAILURE.
 * Post-update steps must treat a non-zero code as a failed step.
 */
final class ArtisanCaller
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function callOrFail(string $command, array $parameters = []): string
    {
        $code = Artisan::call($command, $parameters);
        $output = trim(Artisan::output());

        if ($code !== 0) {
            $message = "Artisan command `{$command}` failed with exit code {$code}.";
            if ($output !== '') {
                $message .= ' '.str_replace(["\r", "\n"], [' ', ' '], $output);
            }

            throw UpdateException::post($message);
        }

        return $output;
    }
}
