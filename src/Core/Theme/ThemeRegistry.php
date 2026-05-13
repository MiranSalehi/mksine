<?php

namespace Miran\Mksine\Core\Theme;

/**
 * Stores theme overrides (component per page) and route callbacks.
 * Used when theme.php calls theme_register_override() / theme_register_routes().
 */
class ThemeRegistry
{
    /** @var array<string, string> Map page key => Livewire component class */
    protected array $overrides = [];

    /** @var array<int, callable> Callbacks that register routes (receive Route facade) */
    protected array $routeCallbacks = [];

    /**
     * Identifier of the theme whose theme.php was already executed this request (prevents
     * duplicate route callbacks when {@see ThemeBootstrap::boot()} runs multiple times for the
     * same active theme).
     */
    protected ?string $themePhpLoadedIdentifier = null;

    public function getThemePhpLoadedIdentifier(): ?string
    {
        return $this->themePhpLoadedIdentifier;
    }

    public function markThemePhpLoaded(string $identifier): void
    {
        $this->themePhpLoadedIdentifier = $identifier;
    }

    public function registerOverride(string $page, string $componentClass): void
    {
        $this->overrides[$page] = $componentClass;
    }

    public function getOverride(string $page): ?string
    {
        return $this->overrides[$page] ?? null;
    }

    /**
     * @param  callable(\Illuminate\Routing\Router): void  $callback
     */
    public function registerRoutes(callable $callback): void
    {
        $this->routeCallbacks[] = $callback;
    }

    /**
     * @return array<int, callable>
     */
    public function getRouteCallbacks(): array
    {
        return $this->routeCallbacks;
    }
}
