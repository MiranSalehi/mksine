<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support\AdminMenu;

use Closure;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Arr;
use LengthException;
use Miran\Mksine\Filament\Clusters\SettingsCluster;
use Miran\Mksine\Filament\Pages\Settings\SettingsMenuPage;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Models\Setting;

/**
 * Super-admin overrides for the admin sidebar, stored as JSON in the {@see self::SETTING_KEY} setting.
 *
 * An empty (or missing) setting means "the menu the code defines" — {@see apply()} returns the
 * groups untouched. Bound as a scoped singleton, so the setting is read at most once per request.
 *
 * Item keys are {@see NavigationItem::getKey()} (the page/resource class for Filament-generated
 * items). Group keys are {@see AdminNavigationGroup} values.
 *
 * @phpstan-type ItemOverride array{placement: AdminMenuPlacement, group: ?string, sort: ?int, label: ?string}
 * @phpstan-type GroupOverride array{sort: ?int, label: ?string, hidden: bool}
 * @phpstan-type Bucket array{group: NavigationGroup, items: list<NavigationItem>, touched: bool}
 */
final class AdminMenuOverrides
{
    public const string SETTING_KEY = 'mksine_admin_menu';

    /** Matches the `settings.value` column length. */
    public const int MAX_STORED_LENGTH = 8192;

    /**
     * Items that always stay reachable from the sidebar (they can still be moved, sorted and renamed).
     *
     * @var list<string>
     */
    public const array PROTECTED_ITEM_KEYS = [
        SettingsCluster::class,
        SettingsMenuPage::class,
    ];

    /** @var array<string, ItemOverride>|null */
    private ?array $items = null;

    /** @var array<string, GroupOverride>|null */
    private ?array $groups = null;

    public static function isProtected(string $itemKey): bool
    {
        return in_array($itemKey, self::PROTECTED_ITEM_KEYS, true);
    }

    /**
     * Sort a group has when no override is stored: its {@see AdminNavigationGroup} case order × 10,
     * which is the order Filament already uses for enum groups.
     */
    public static function defaultGroupSort(AdminNavigationGroup $group): int
    {
        return ((int) array_search($group, AdminNavigationGroup::cases(), true) + 1) * 10;
    }

    /**
     * Stable identity of a sidebar group: '' for top-level items, an {@see AdminNavigationGroup}
     * value for core groups, or `custom:{label}` for groups a plugin registered on its own.
     */
    public static function groupKey(NavigationGroup $group): string
    {
        $label = $group->getLabel();

        if (blank($label)) {
            return '';
        }

        $firstItem = Arr::first(collect($group->getItems())->all());

        if ($firstItem instanceof NavigationItem && $firstItem->getGroup() instanceof AdminNavigationGroup) {
            return $firstItem->getGroup()->value;
        }

        foreach (AdminNavigationGroup::cases() as $case) {
            if ($case->getLabel() === $label) {
                return $case->value;
            }
        }

        return 'custom:' . $label;
    }

    public function isEmpty(): bool
    {
        return $this->itemOverrides() === [] && $this->groupOverrides() === [];
    }

    /**
     * @return array<string, ItemOverride>
     */
    public function itemOverrides(): array
    {
        $this->load();

        return $this->items;
    }

    /**
     * @return array<string, GroupOverride>
     */
    public function groupOverrides(): array
    {
        $this->load();

        return $this->groups;
    }

    /**
     * @return ItemOverride|null
     */
    public function itemOverride(string $itemKey): ?array
    {
        return $this->itemOverrides()[$itemKey] ?? null;
    }

    /**
     * @return GroupOverride|null
     */
    public function groupOverride(string $groupKey): ?array
    {
        return $this->groupOverrides()[$groupKey] ?? null;
    }

    /**
     * Apply the stored overrides to sidebar groups. Input groups and items are cloned, never mutated.
     *
     * @param  list<NavigationGroup>  $groups
     * @param  (Closure(AdminNavigationGroup): NavigationGroup)|null  $makeGroup  Builds a group that has no items by default.
     */
    public function apply(array $groups, ?Closure $makeGroup = null): AdminMenuResolution
    {
        if ($this->isEmpty()) {
            return new AdminMenuResolution(array_values($groups), []);
        }

        $makeGroup ??= AdminSidebarNavigation::makeGroup(...);

        /** @var array<string, Bucket> $buckets */
        $buckets = [];

        foreach ($groups as $group) {
            $key = self::groupKey($group);

            // Two groups resolving to one key would merge on move; keep the extra one untouchable.
            while (array_key_exists($key, $buckets)) {
                $key .= '#';
            }

            $buckets[$key] = [
                'group' => clone $group,
                'items' => array_values(array_map(
                    fn (NavigationItem $item): NavigationItem => clone $item,
                    collect($group->getItems())->all(),
                )),
                'touched' => false,
            ];
        }

        /** @var array<string, list<NavigationItem>> $movedIn */
        $movedIn = [];
        $settingsItems = [];

        foreach ($buckets as $sourceKey => $bucket) {
            $kept = [];

            foreach ($bucket['items'] as $item) {
                $itemKey = $item->getKey();
                $override = $this->itemOverride($itemKey);

                if ($override === null) {
                    $kept[] = $item;

                    continue;
                }

                if ($override['label'] !== null) {
                    $item->label($override['label']);
                }

                if ($override['sort'] !== null) {
                    $item->sort($override['sort']);
                    $buckets[$sourceKey]['touched'] = true;
                }

                $placement = $override['placement'];

                if ($placement->removesFromSidebar() && self::isProtected($itemKey)) {
                    $placement = AdminMenuPlacement::Default;
                }

                if ($placement === AdminMenuPlacement::Hidden) {
                    continue;
                }

                if ($placement === AdminMenuPlacement::Settings) {
                    $settingsItems[] = $item->group(__('mksine::admin_menu.settings_navigation_group'));

                    continue;
                }

                if ($placement === AdminMenuPlacement::Group && $override['group'] !== null && $override['group'] !== $sourceKey) {
                    $movedIn[$override['group']][] = $item->group(AdminNavigationGroup::from($override['group']));

                    continue;
                }

                $kept[] = $item;
            }

            $buckets[$sourceKey]['items'] = $kept;
        }

        foreach ($movedIn as $targetKey => $items) {
            $target = AdminNavigationGroup::from($targetKey);

            if (! array_key_exists($targetKey, $buckets)) {
                $buckets = $this->insertBucket($buckets, $target, $makeGroup($target));
            }

            $buckets[$targetKey]['items'] = [...$buckets[$targetKey]['items'], ...$items];
            $buckets[$targetKey]['touched'] = true;
        }

        foreach ($buckets as $key => $bucket) {
            if ($this->groupOverride((string) $key)['hidden'] ?? false) {
                // A hidden group still shows protected items, so Settings and this editor stay reachable.
                $buckets[$key]['items'] = array_values(array_filter(
                    $bucket['items'],
                    fn (NavigationItem $item): bool => self::isProtected($item->getKey()),
                ));
            }
        }

        $buckets = array_filter($buckets, fn (array $bucket): bool => $bucket['items'] !== []);

        foreach ($buckets as $key => $bucket) {
            $items = $bucket['items'];

            if ($bucket['touched']) {
                // usort is stable, so equal sorts keep the order Filament produced.
                usort($items, fn (NavigationItem $a, NavigationItem $b): int => $a->getSort() <=> $b->getSort());
            }

            $bucket['group']->items($items);

            if (($label = $this->groupOverride((string) $key)['label'] ?? null) !== null) {
                $bucket['group']->label($label);
            }
        }

        return new AdminMenuResolution(
            $this->sortGroups($buckets),
            $settingsItems,
        );
    }

    /**
     * Persist overrides (same shape as the stored JSON). Entries equal to the default are dropped,
     * and an empty result deletes the setting.
     *
     * @param  array<string, mixed>  $overrides
     *
     * @throws LengthException When the encoded JSON does not fit the settings column.
     */
    public function save(array $overrides): void
    {
        $normalized = self::normalize($overrides);

        if ($normalized['items'] === [] && $normalized['groups'] === []) {
            $this->reset();

            return;
        }

        $json = json_encode(self::serialize($normalized), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (mb_strlen($json) > self::MAX_STORED_LENGTH) {
            throw new LengthException(__('mksine::admin_menu.notifications.too_large'));
        }

        Setting::query()->updateOrCreate(['key' => self::SETTING_KEY], ['value' => $json]);

        $this->flush();
    }

    /**
     * Back to the menu the code defines.
     */
    public function reset(): void
    {
        Setting::query()->where('key', self::SETTING_KEY)->get()->each->delete();

        $this->flush();
    }

    /**
     * Forget the memoized setting so the next read hits the database.
     */
    public function flush(): void
    {
        $this->items = null;
        $this->groups = null;
    }

    /**
     * Stored overrides in their JSON shape, for editing.
     *
     * @return array{items: array<string, array<string, int|string>>, groups: array<string, array<string, bool|int|string>>}
     */
    public function toArray(): array
    {
        return self::serialize(['items' => $this->itemOverrides(), 'groups' => $this->groupOverrides()]);
    }

    private function load(): void
    {
        if ($this->items !== null && $this->groups !== null) {
            return;
        }

        $normalized = self::normalize(self::readStored());

        $this->items = $normalized['items'];
        $this->groups = $normalized['groups'];
    }

    /**
     * @return array<string, mixed>
     */
    private static function readStored(): array
    {
        try {
            $raw = Setting::query()->where('key', self::SETTING_KEY)->value('value');
        } catch (\Throwable) {
            // Settings table not migrated yet (install, package tests): behave as "no overrides".
            return [];
        }

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<mixed>  $raw
     * @return array{items: array<string, ItemOverride>, groups: array<string, GroupOverride>}
     */
    private static function normalize(array $raw): array
    {
        $items = [];

        foreach (is_array($raw['items'] ?? null) ? $raw['items'] : [] as $key => $entry) {
            if (! is_string($key) || $key === '' || ! is_array($entry)) {
                continue;
            }

            $placement = AdminMenuPlacement::tryFrom((string) ($entry['placement'] ?? '')) ?? AdminMenuPlacement::Default;
            $group = AdminNavigationGroup::tryFrom((string) ($entry['group'] ?? ''))?->value;

            if ($placement === AdminMenuPlacement::Group && $group === null) {
                $placement = AdminMenuPlacement::Default;
            }

            $override = [
                'placement' => $placement,
                'group' => $placement === AdminMenuPlacement::Group ? $group : null,
                'sort' => self::normalizeSort($entry['sort'] ?? null),
                'label' => self::normalizeLabel($entry['label'] ?? null),
            ];

            if ($placement === AdminMenuPlacement::Default && $override['sort'] === null && $override['label'] === null) {
                continue;
            }

            $items[$key] = $override;
        }

        $groups = [];

        foreach (is_array($raw['groups'] ?? null) ? $raw['groups'] : [] as $key => $entry) {
            if (! is_string($key) || AdminNavigationGroup::tryFrom($key) === null || ! is_array($entry)) {
                continue;
            }

            $override = [
                'sort' => self::normalizeSort($entry['sort'] ?? null),
                'label' => self::normalizeLabel($entry['label'] ?? null),
                'hidden' => filter_var($entry['hidden'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            if ($override['sort'] === null && $override['label'] === null && ! $override['hidden']) {
                continue;
            }

            $groups[$key] = $override;
        }

        return ['items' => $items, 'groups' => $groups];
    }

    /**
     * @param  array{items: array<string, ItemOverride>, groups: array<string, GroupOverride>}  $normalized
     * @return array{items: array<string, array<string, int|string>>, groups: array<string, array<string, bool|int|string>>}
     */
    private static function serialize(array $normalized): array
    {
        $items = [];

        foreach ($normalized['items'] as $key => $override) {
            $items[$key] = array_filter([
                'placement' => $override['placement']->value,
                'group' => $override['group'],
                'sort' => $override['sort'],
                'label' => $override['label'],
            ], fn (mixed $value): bool => $value !== null);
        }

        $groups = [];

        foreach ($normalized['groups'] as $key => $override) {
            $groups[$key] = array_filter(
                [...$override, 'hidden' => $override['hidden'] ?: null],
                fn (mixed $value): bool => $value !== null,
            );
        }

        return ['items' => $items, 'groups' => $groups];
    }

    private static function normalizeSort(mixed $sort): ?int
    {
        if (is_int($sort)) {
            return $sort;
        }

        // Filament numeric inputs dehydrate to float.
        if (is_float($sort) && is_finite($sort) && floor($sort) === $sort) {
            return (int) $sort;
        }

        if (is_string($sort) && preg_match('/^-?\d+$/', trim($sort)) === 1) {
            return (int) trim($sort);
        }

        return null;
    }

    private static function normalizeLabel(mixed $label): ?string
    {
        if (! is_string($label)) {
            return null;
        }

        $label = trim($label);

        return $label === '' ? null : $label;
    }

    /**
     * Insert a bucket for a group that had no items, where Filament would have placed it (enum case order).
     *
     * @param  array<string, Bucket>  $buckets
     * @return array<string, Bucket>
     */
    private function insertBucket(array $buckets, AdminNavigationGroup $target, NavigationGroup $group): array
    {
        $targetSort = self::defaultGroupSort($target);
        $result = [];
        $inserted = false;

        foreach ($buckets as $key => $bucket) {
            $existing = AdminNavigationGroup::tryFrom((string) $key);

            if (! $inserted && $existing !== null && self::defaultGroupSort($existing) > $targetSort) {
                $result[$target->value] = ['group' => $group, 'items' => [], 'touched' => true];
                $inserted = true;
            }

            $result[$key] = $bucket;
        }

        if (! $inserted) {
            $result[$target->value] = ['group' => $group, 'items' => [], 'touched' => true];
        }

        return $result;
    }

    /**
     * Keep Filament's order unless a group sort override exists. Groups without an override sort by
     * {@see defaultGroupSort()}; plugin groups stay right after the group they followed. Top-level
     * items (key '') always come first, like in Filament.
     *
     * @param  array<string, Bucket>  $buckets
     * @return list<NavigationGroup>
     */
    private function sortGroups(array $buckets): array
    {
        $hasSortOverride = collect($this->groupOverrides())
            ->contains(fn (array $override, string $key): bool => $override['sort'] !== null && array_key_exists($key, $buckets));

        if (! $hasSortOverride) {
            return array_values(array_column($buckets, 'group'));
        }

        $rows = [];
        $previousDefault = PHP_INT_MIN;
        $position = 0;

        foreach ($buckets as $key => $bucket) {
            $key = (string) $key;
            $case = AdminNavigationGroup::tryFrom($key);
            $default = $case !== null ? self::defaultGroupSort($case) : $previousDefault;
            $previousDefault = $default;

            $rows[] = [
                'group' => $bucket['group'],
                'sort' => $key === '' ? PHP_INT_MIN : ($this->groupOverride($key)['sort'] ?? $default),
                'position' => $position++,
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['sort'], $a['position']] <=> [$b['sort'], $b['position']]);

        return array_column($rows, 'group');
    }
}
