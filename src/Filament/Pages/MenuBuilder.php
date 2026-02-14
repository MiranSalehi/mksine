<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface;
use Miran\Mksine\Core\Hooks\MenuItemSourceManager;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuItem;

class MenuBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static string | \UnitEnum | null $navigationGroup = 'Appearance';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'mksine::filament.pages.menu-builder';

    #[Url]
    public ?int $menu = null;

    public ?Menu $selectedMenu = null;

    public array $menuItems = [];

    public array $sources = [];

    // Form states for each source
    public array $sourceFormData = [];

    /** Paginated list data per source: [key => ['items' => [], 'total' => 0, 'current_page' => 1, 'per_page' => 10]] */
    public array $sourceItems = [];

    /** Search term per source (for list sources) */
    public array $sourceSearch = [];

    /** Current page per source (for list sources) */
    public array $sourcePage = [];

    private const SOURCE_ITEMS_PER_PAGE = 10;

    public function mount(): void
    {
        if ($this->menu) {
            $this->selectedMenu = Menu::find($this->menu);
            $this->loadMenuItems();
        }

        $this->loadSources();
    }

    public static function getNavigationLabel(): string
    {
        return __('Menu Builder');
    }

    public function getTitle(): string
    {
        return __('Menu Builder');
    }

    public function getSubheading(): ?string
    {
        if ($this->selectedMenu) {
            return __('Editing: :menu', ['menu' => $this->selectedMenu->name]);
        }

        return __('Select a menu to edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('selectMenu')
                ->label(__('Select Menu'))
                ->icon('heroicon-o-queue-list')
                ->form([
                    Select::make('menu_id')
                        ->label(__('Menu'))
                        ->options(Menu::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $this->menu = $data['menu_id'];
                    $this->selectedMenu = Menu::find($this->menu);
                    $this->loadMenuItems();
                }),

            Action::make('save')
                ->label(__('Save Menu'))
                ->icon('heroicon-o-check')
                ->color('primary')
                ->visible(fn () => $this->selectedMenu !== null)
                ->action(fn () => $this->saveMenu()),
        ];
    }

    protected function loadSources(): void
    {
        $sourceManager = app(MenuItemSourceManager::class);
        $this->sources = [];

        foreach ($sourceManager->getSources() as $key => $source) {
            $hasCustomForm = $source->getFormSchema() !== null;
            $supportsMultiple = $source->supportsMultipleSelection();
            $isListSource = ! $hasCustomForm && $supportsMultiple;

            $this->sources[$key] = [
                'key' => $source->getKey(),
                'label' => $source->getLabel(),
                'icon' => $source->getIcon(),
                'items' => [], // No longer loaded here; use getSourceItems() for list sources
                'supportsMultiple' => $supportsMultiple,
                'hasCustomForm' => $hasCustomForm,
                'isListSource' => $isListSource,
            ];

            $this->sourceFormData[$key] = [
                'selected' => [],
                'url' => '',
                'label' => '',
            ];
        }
    }

    /**
     * Load paginated/filtered items for a list source (called when panel expands or search/page changes).
     */
    public function getSourceItems(string $sourceKey): void
    {
        $sourceManager = app(MenuItemSourceManager::class);
        $source = $sourceManager->getSource($sourceKey);

        if (! $source || ! ($this->sources[$sourceKey]['isListSource'] ?? false)) {
            return;
        }

        $search = $this->sourceSearch[$sourceKey] ?? '';
        $page = (int) ($this->sourcePage[$sourceKey] ?? 1);
        $perPage = self::SOURCE_ITEMS_PER_PAGE;

        if ($source instanceof MenuItemSourcePaginatedInterface) {
            $result = $source->getItemsPaginated($search, $page, $perPage);
            $this->sourceItems[$sourceKey] = [
                'items' => $result['items'],
                'total' => $result['total'],
                'current_page' => $page,
                'per_page' => $perPage,
            ];

            return;
        }

        // Fallback: getItems() then filter and slice (for sources that don't implement getItemsPaginated)
        $all = $source->getItems();
        $filtered = $search !== ''
            ? array_values(array_filter($all, fn ($item) => stripos((string) ($item['label'] ?? ''), $search) !== false))
            : $all;
        $total = count($filtered);
        $slice = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        $this->sourceItems[$sourceKey] = [
            'items' => $slice,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function setSourceSearch(string $sourceKey, string $value): void
    {
        $this->sourceSearch[$sourceKey] = $value;
        $this->sourcePage[$sourceKey] = 1;
        $this->getSourceItems($sourceKey);
    }

    public function setSourcePage(string $sourceKey, int $page): void
    {
        $this->sourcePage[$sourceKey] = max(1, $page);
        $this->getSourceItems($sourceKey);
    }

    /**
     * Build tree order and depth for items that have parent_id (e.g. categories).
     * Returns the same items with 'depth' (0 = root) added, sorted so parent always comes before children.
     * If no item has parent_id, returns items unchanged with depth 0.
     *
     * @param  array<int, array{id: int, label: string, url: string, parent_id?: int|null}>  $items
     * @return array<int, array{id: int, label: string, url: string, parent_id?: int|null, depth: int}>
     */
    public function getSourceItemsTreeOrder(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $hasParentId = collect($items)->contains(fn ($item) => array_key_exists('parent_id', $item));
        if (! $hasParentId) {
            return array_map(fn ($item) => array_merge($item, ['depth' => 0]), array_values($items));
        }

        $map = [];
        foreach ($items as $item) {
            $item = array_merge($item, ['depth' => 0]);
            $map[$item['id']] = $item;
        }

        $maxIterations = count($map);
        for ($i = 0; $i < $maxIterations; $i++) {
            $changed = false;
            foreach ($map as $id => $item) {
                $pid = $item['parent_id'] ?? null;
                if ($pid !== null) {
                    if (isset($map[$pid])) {
                        $newDepth = $map[$pid]['depth'] + 1;
                    } else {
                        $newDepth = 1;
                    }
                    if ($item['depth'] !== $newDepth) {
                        $map[$id]['depth'] = $newDepth;
                        $changed = true;
                    }
                }
            }
            if (! $changed) {
                break;
            }
        }

        $buildPath = function (int $id) use (&$buildPath, $map): array {
            $item = $map[$id] ?? null;
            if (! $item) {
                return [$id];
            }
            $pid = $item['parent_id'] ?? null;
            if ($pid === null || ! isset($map[$pid])) {
                return [$id];
            }

            return array_merge($buildPath($pid), [$id]);
        };

        $withPath = [];
        foreach ($map as $item) {
            $withPath[] = ['path' => $buildPath($item['id']), 'item' => $item];
        }
        usort($withPath, fn ($a, $b) => $a['path'] <=> $b['path']);

        return array_map(fn ($x) => $x['item'], $withPath);
    }

    protected function loadMenuItems(): void
    {
        if (! $this->selectedMenu) {
            $this->menuItems = [];

            return;
        }

        $items = $this->selectedMenu->items()
            ->whereNull('parent_id')
            ->orderBy('order')
            ->with('children')
            ->get();

        $this->menuItems = $this->buildItemsArray($items);
    }

    protected function buildItemsArray(Collection $items): array
    {
        return $items->map(function (MenuItem $item) {
            return [
                'id' => $item->id,
                'type' => $item->type,
                'label' => $item->label,
                'url' => $item->url,
                'reference_id' => $item->reference_id,
                'target' => $item->target,
                'order' => $item->order,
                'children' => $this->buildItemsArray($item->children->sortBy('order')),
            ];
        })->values()->toArray();
    }

    public function addItemFromSource(string $sourceKey): void
    {
        if (! $this->selectedMenu) {
            Notification::make()
                ->title(__('Please select a menu first'))
                ->warning()
                ->send();

            return;
        }

        $sourceManager = app(MenuItemSourceManager::class);
        $source = $sourceManager->getSource($sourceKey);

        if (! $source) {
            return;
        }

        $formData = $this->sourceFormData[$sourceKey] ?? [];

        if ($source->supportsMultipleSelection()) {
            // Handle checkbox selection: resolve selected IDs to items (prefer getItemsByIds if available)
            $selectedIds = array_filter(array_map('intval', $formData['selected'] ?? []));
            $items = $source instanceof MenuItemSourcePaginatedInterface
                ? $source->getItemsByIds($selectedIds)
                : collect($source->getItems())->whereIn('id', $selectedIds)->all();

            foreach ($items as $item) {
                $this->createMenuItem($source->toMenuItem($item));
            }

            // Clear selection
            $this->sourceFormData[$sourceKey]['selected'] = [];
        } else {
            // Handle custom form (like custom link)
            $this->createMenuItem($source->toMenuItem($formData));

            // Clear form
            $this->sourceFormData[$sourceKey] = [
                'url' => '',
                'label' => '',
            ];
        }

        Notification::make()
            ->title(__('Item(s) added'))
            ->success()
            ->send();
    }

    protected function createMenuItem(array $data): void
    {
        $maxOrder = MenuItem::where('menu_id', $this->selectedMenu->id)
            ->whereNull('parent_id')
            ->max('order') ?? -1;

        $item = MenuItem::create([
            'menu_id' => $this->selectedMenu->id,
            'parent_id' => null,
            'type' => $data['type'],
            'label' => $data['label'],
            'url' => $data['url'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'order' => $maxOrder + 1,
            'target' => $data['target'] ?? '_self',
        ]);

        // Add to local state
        $this->menuItems[] = [
            'id' => $item->id,
            'type' => $item->type,
            'label' => $item->label,
            'url' => $item->url,
            'reference_id' => $item->reference_id,
            'target' => $item->target,
            'order' => $item->order,
            'children' => [],
        ];
    }

    public function removeItem(int $itemId): void
    {
        MenuItem::where('id', $itemId)->delete();

        $this->menuItems = $this->removeItemFromArray($this->menuItems, $itemId);

        Notification::make()
            ->title(__('Item removed'))
            ->success()
            ->send();
    }

    protected function removeItemFromArray(array $items, int $itemId): array
    {
        return collect($items)
            ->reject(fn (array $item) => $item['id'] === $itemId)
            ->map(function (array $item) use ($itemId) {
                $item['children'] = $this->removeItemFromArray($item['children'], $itemId);

                return $item;
            })
            ->values()
            ->toArray();
    }

    public function editItemAction(): Action
    {
        return Action::make('editItem')
            ->label(__('Edit Item'))
            ->modalWidth('md')
            ->fillForm(function (array $arguments) {
                $item = MenuItem::find($arguments['itemId']);

                return $item ? $item->toArray() : [];
            })
            ->form(function (array $arguments) {
                $item = MenuItem::find($arguments['itemId']);
                $type = $item?->type;

                $schema = [
                    TextInput::make('label')
                        ->label(__('Label'))
                        ->required(),

                    Select::make('target')
                        ->label(__('Target'))
                        ->options([
                            '_self' => __('Same Tab'),
                            '_blank' => __('New Tab'),
                        ])
                        ->default('_self')
                        ->required(),
                ];

                if ($type === MenuItem::TYPE_CUSTOM_LINK) {
                    $schema[] = TextInput::make('url')
                        ->label(__('URL'))
                        ->required()
                        ->url();
                }

                return $schema;
            })
            ->action(function (array $data, array $arguments) {
                $item = MenuItem::find($arguments['itemId']);
                if (! $item) {
                    return;
                }

                $item->update($data);

                $this->loadMenuItems();

                Notification::make()
                    ->title(__('Item updated'))
                    ->success()
                    ->send();
            });
    }

    public function updateMenuStructure(array $structure): void
    {
        if (! $this->selectedMenu) {
            return;
        }

        // Update database with new structure
        $order = 0;
        $this->saveStructureRecursive($structure, null, $order);

        // Reload items from database to ensure we have all fields (type, label, etc.)
        // The structure from JS only contains id and children
        $this->loadMenuItems();

        Notification::make()
            ->title(__('Menu structure updated'))
            ->success()
            ->send();
    }

    protected function saveStructureRecursive(array $items, ?int $parentId, int &$order): void
    {
        foreach ($items as $item) {
            MenuItem::where('id', $item['id'])->update([
                'parent_id' => $parentId,
                'order' => $order++,
            ]);

            if (! empty($item['children'])) {
                $childOrder = 0;
                $this->saveStructureRecursive($item['children'], $item['id'], $childOrder);
            }
        }
    }

    public function saveMenu(): void
    {
        if (! $this->selectedMenu) {
            return;
        }

        // Save the current structure
        $order = 0;
        $this->saveStructureRecursive($this->menuItems, null, $order);

        Notification::make()
            ->title(__('Menu saved successfully'))
            ->success()
            ->send();
    }
}
