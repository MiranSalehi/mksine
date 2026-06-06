<?php

declare(strict_types=1);

namespace Miran\Mksine\Enums;

enum GeoSource: string
{
    case Seed = 'seed';
    case Manual = 'manual';
}
