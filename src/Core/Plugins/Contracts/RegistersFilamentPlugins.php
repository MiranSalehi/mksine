<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins\Contracts;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Optional contract for {@see PluginInterface} implementations that register
 * additional Filament {@see Plugin} instances on the admin panel (e.g. a
 * vendor Filament package configured by the plugin, without touching the app panel provider).
 */
interface RegistersFilamentPlugins
{
    /**
     * @return array<int, Plugin>
     */
    public function filamentPlugins(Panel $panel): array;
}
