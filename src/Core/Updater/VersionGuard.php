<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

final class VersionGuard
{
    public static function assertUpgrade(string $fromVersion, string $toVersion, bool $force): void
    {
        if ($force) {
            return;
        }

        $comparison = version_compare($toVersion, $fromVersion);

        if ($comparison === 0) {
            $allowSame = (bool) config('mksine.updater.allow_same_version_reinstall', false);
            if ($allowSame) {
                return;
            }

            throw UpdateException::validation(
                "Already at version {$fromVersion}. Use --force on CLI to reinstall, or enable mksine.updater.allow_same_version_reinstall."
            );
        }

        if ($comparison < 0) {
            throw UpdateException::validation(
                "Downgrade rejected: {$fromVersion} -> {$toVersion}. Use --force on CLI to override."
            );
        }
    }
}
