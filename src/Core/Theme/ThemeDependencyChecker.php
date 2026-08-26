<?php

namespace Miran\Mksine\Core\Theme;

use Illuminate\Support\Collection;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Plugins\PluginRegistry;

/**
 * Resolves plugin dependencies declared in a theme's theme.json manifest.
 */
final class ThemeDependencyChecker
{
    public function __construct(
        private PluginRegistry $plugins,
        private PluginManager $pluginManager,
        private ThemeManager $themes,
    ) {}

    /**
     * @return list<string>
     */
    public function requiredPlugins(ThemeData $theme): array
    {
        $requires = $theme->requires;

        if (! is_array($requires)) {
            return [];
        }

        $plugins = $requires['plugins'] ?? [];

        if (! is_array($plugins)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $pluginId): string => trim((string) $pluginId),
            $plugins
        ))));
    }

    /**
     * @return list<string>
     */
    public function missingPlugins(?ThemeData $theme = null): array
    {
        $theme ??= $this->themes->getActive();

        if (! $theme) {
            return [];
        }

        return array_values(array_filter(
            $this->requiredPlugins($theme),
            fn (string $pluginId): bool => ! $this->plugins->isActive($pluginId)
        ));
    }

    public function isSatisfied(?ThemeData $theme = null): bool
    {
        return $this->missingPlugins($theme) === [];
    }

    public function pluginLabel(string $pluginId): string
    {
        $manifest = $this->pluginManager->getManifest($pluginId);

        if ($manifest !== null) {
            return $manifest->name();
        }

        return $pluginId;
    }

    /**
     * @return list<string>
     */
    public function missingPluginLabels(?ThemeData $theme = null): array
    {
        return array_map(
            fn (string $pluginId): string => $this->pluginLabel($pluginId),
            $this->missingPlugins($theme)
        );
    }

    /**
     * @return Collection<string, ThemeData>
     */
    public function themesRequiringPlugin(string $pluginId): Collection
    {
        return $this->themes->discover()->filter(
            fn (ThemeData $theme): bool => in_array($pluginId, $this->requiredPlugins($theme), true)
        );
    }

    public function activeThemeRequiresPlugin(string $pluginId): bool
    {
        $activeTheme = $this->themes->getActive();

        if (! $activeTheme) {
            return false;
        }

        return in_array($pluginId, $this->requiredPlugins($activeTheme), true);
    }
}
