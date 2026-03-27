<?php

namespace Miran\Mksine\Core\Theme;

/**
 * Passed into theme.php; theme uses this to register overrides and routes.
 */
class ThemeRegistrar
{
    public function __construct(
        protected ThemeRegistry $registry
    ) {}

    public function registerOverride(string $page, string $componentClass): void
    {
        $this->registry->registerOverride($page, $componentClass);
    }

    /**
     * @param  callable(\Illuminate\Routing\Router): void  $callback
     */
    public function registerRoutes(callable $callback): void
    {
        $this->registry->registerRoutes($callback);
    }
}
