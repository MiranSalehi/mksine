<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

use InvalidArgumentException;

enum MarketplaceKind: string
{
    case Plugin = 'plugin';
    case Theme = 'theme';

    public function catalogPath(): string
    {
        return match ($this) {
            self::Plugin => 'plugins',
            self::Theme => 'themes',
        };
    }

    public static function fromCatalog(string $kind): self
    {
        return match ($kind) {
            'plugin', 'plugins' => self::Plugin,
            'theme', 'themes' => self::Theme,
            default => throw new InvalidArgumentException('Unknown marketplace kind.'),
        };
    }
}
