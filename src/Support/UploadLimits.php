<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

/**
 * Central upload size limits for MKSine admin features (media, plugin/theme ZIPs).
 */
final class UploadLimits
{
    public static function maxSizeMb(): int
    {
        return max(1, (int) config('mksine.uploads.max_size_mb', 100));
    }

    public static function maxSizeKb(): int
    {
        return self::maxSizeMb() * 1024;
    }

    public static function updaterMaxZipMb(): int
    {
        return max(1, (int) config('mksine.updater.max_zip_size_mb', self::maxSizeMb()));
    }

    public static function updaterMaxZipKb(): int
    {
        return self::updaterMaxZipMb() * 1024;
    }

    public static function mediaMaxKb(): int
    {
        return max(1, (int) config('mksine.media.max_size', self::maxSizeKb()));
    }
}
