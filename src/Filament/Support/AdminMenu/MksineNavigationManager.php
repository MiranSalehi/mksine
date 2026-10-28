<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support\AdminMenu;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Filament\Panel;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\MksinePlugin;

/**
 * The only {@see NavigationManager} binding in an MKSine app (registered by {@see MksinePlugin}).
 *
 * On panels that use MksinePlugin, {@see get()} runs:
 *   1. Filament's own navigation ({@see parent::get()})
 *   2. the {@see self::GROUPS_FILTER} filter, so plugins can add/remove/reorder items
 *   3. the super-admin overrides from {@see AdminMenuOverrides} (last word)
 *
 * Other panels get Filament's navigation unchanged. Plugins must not bind NavigationManager
 * themselves — two bindings silently replace each other; use the filter instead.
 */
class MksineNavigationManager extends NavigationManager
{
    /**
     * Filter: `list<NavigationGroup>` in, `list<NavigationGroup>` out; second argument is the Panel.
     */
    public const string GROUPS_FILTER = 'mksine.admin.navigation.groups';

    /** @var list<NavigationGroup>|null */
    protected ?array $defaultNavigation = null;

    protected ?AdminMenuResolution $resolution = null;

    /**
     * Called by {@see Panel::getNavigation()}.
     *
     * @return array<NavigationGroup>
     */
    public function get(): array
    {
        if (! $this->managesPanel()) {
            return parent::get();
        }

        return $this->resolve()->groups;
    }

    public function managesPanel(): bool
    {
        return $this->panel->hasPlugin('mksine');
    }

    /**
     * Navigation after the plugin filter but before super-admin overrides — what the menu editor edits.
     *
     * @return list<NavigationGroup>
     */
    public function getDefaultNavigation(): array
    {
        $this->resolveThroughPanel();

        return $this->defaultNavigation ?? [];
    }

    /**
     * Items a super admin moved from the sidebar into the Settings sub-navigation.
     *
     * @return list<NavigationItem>
     */
    public function getSettingsItems(): array
    {
        $this->resolveThroughPanel();

        return $this->resolution?->settingsItems ?? [];
    }

    /**
     * Re-apply the overrides (after they were saved) without mounting navigation again.
     */
    public function refresh(): AdminMenuResolution
    {
        $this->resolveThroughPanel();
        $this->resolution = null;

        return $this->resolve();
    }

    protected function resolve(): AdminMenuResolution
    {
        return $this->resolution ??= app(AdminMenuOverrides::class)->apply(
            $this->defaultNavigation ??= $this->filterNavigation(array_values(parent::get())),
            $this->makeGroup(...),
        );
    }

    /**
     * A hand-made NavigationItem without ->key() falls back to its translated label, so an override
     * saved in one locale would miss in another. Such items are keyed by their URL path instead.
     *
     * @param  list<NavigationGroup>  $groups
     * @return list<NavigationGroup>
     */
    protected function keyItemsByUrl(array $groups): array
    {
        foreach ($groups as $group) {
            foreach (collect($group->getItems())->all() as $item) {
                if (! $item instanceof NavigationItem || self::hasExplicitKey($item)) {
                    continue;
                }

                $url = parse_url((string) $item->getUrl());
                $path = is_array($url) ? ($url['path'] ?? '') : '';

                if ($path === '') {
                    continue;
                }

                $item->key('url:' . $path . (isset($url['query']) ? '?' . $url['query'] : ''));
            }
        }

        return $groups;
    }

    protected static function hasExplicitKey(NavigationItem $item): bool
    {
        return (fn (): mixed => $this->key)->call($item) !== null;
    }

    /**
     * Pages register their items through Panel::navigationItems(), which only forwards them to the
     * manager while Panel::getNavigation() runs. Mounting outside of it would append the items to
     * the panel itself, so callers outside the sidebar always go through the panel.
     */
    protected function resolveThroughPanel(): void
    {
        if ($this->resolution !== null || ! $this->managesPanel()) {
            return;
        }

        if (app(NavigationManager::class) === $this) {
            $this->panel->getNavigation();
        }
    }

    /**
     * @param  list<NavigationGroup>  $groups
     * @return list<NavigationGroup>
     */
    protected function filterNavigation(array $groups): array
    {
        $groups = $this->keyItemsByUrl($groups);
        $filtered = Hooks::filter(self::GROUPS_FILTER, $groups, $this->panel);

        if (! is_array($filtered)) {
            return $groups;
        }

        // Again for items a listener added without a key.
        return $this->keyItemsByUrl(array_values(array_filter($filtered, fn (mixed $group): bool => $group instanceof NavigationGroup)));
    }

    /**
     * Use the group registered on the panel when there is one, so custom labels/icons survive a move.
     */
    protected function makeGroup(AdminNavigationGroup $group): NavigationGroup
    {
        $registered = $this->getNavigationGroups()[$group->name] ?? null;

        return $registered instanceof NavigationGroup
            ? clone $registered
            : AdminSidebarNavigation::makeGroup($group);
    }
}
