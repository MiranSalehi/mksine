<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use DateTimeInterface;

final class MediaStoragePath
{
    public const string PREFIX = 'media';

    public static function datedDirectory(?DateTimeInterface $at = null): string
    {
        $at ??= now();

        return self::PREFIX.'/'.$at->format('Y/m/d');
    }

    public static function relativePath(string $fileName, ?DateTimeInterface $at = null): string
    {
        return self::datedDirectory($at).'/'.$fileName;
    }
}
