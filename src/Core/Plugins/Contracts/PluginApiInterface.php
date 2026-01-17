<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins\Contracts;

/**
 * Interface for plugins that expose a Public API.
 *
 * Plugins implementing this interface can be accessed by other plugins
 * through their public API facade.
 */
interface PluginApiInterface
{
    /**
     * Get the facade class for this plugin's public API.
     *
     * @return string|null Fully qualified class name of the Facade
     */
    public static function getFacadeClass(): ?string;

    /**
     * Get the service container binding name.
     */
    public static function getContainerBinding(): string;
}
