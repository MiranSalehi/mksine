<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use LengthException;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Filament\Clusters\SettingsCluster;
use Miran\Mksine\Filament\Pages\Settings\Concerns\HasAdminMenuSettingsLinks;
use Miran\Mksine\Filament\Support\AdminMenu\AdminMenuOverrides;
use Miran\Mksine\Filament\Support\AdminMenu\AdminMenuPlacement;
use Miran\Mksine\Filament\Support\AdminMenu\AdminMenuResolution;
use Miran\Mksine\Filament\Support\AdminMenu\MksineNavigationManager;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;

/**
 * Super-admin editor for the admin sidebar (hide / move / sort / rename items and groups).
 *
 * @phpstan-type MenuRow array{key: string, label: string, default_group_key: string, default_group_label: string, default_sort: int, placement: string, target_group: ?string, target_group_label: ?string, sort: ?int, custom_label: ?string, is_protected: bool, group_hidden: bool}
 */
class SettingsMenuPage extends Page implements HasTable
{
    use HasAdminMenuSettingsLinks;
    use InteractsWithTable;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'admin-menu';

    protected static ?int $navigationSort = 190;

    public static function getNavigationLabel(): string
    {
        return __('mksine::admin_menu.navigation_label');
    }

    public function getTitle(): string
    {
        return __('mksine::admin_menu.title');
    }

    public function getSubheading(): ?string
    {
        return __('mksine::admin_menu.subheading');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return SuperAdminGate::check();
    }

    public static function canAccess(): bool
    {
        return SuperAdminGate::check();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make(__('mksine::admin_menu.hidden_notice'))
                ->color('warning'),
            EmbeddedTable::make(),
            Section::make(__('mksine::admin_menu.preview.heading'))
                ->description(__('mksine::admin_menu.preview.description'))
                ->icon(Heroicon::OutlinedEye)
                ->collapsible()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])
                        ->schema($this->previewComponents($this->resolveMenu())),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->menuRows())
            ->columns([
                TextColumn::make('label')
                    ->label(__('mksine::admin_menu.columns.label'))
                    ->weight('medium')
                    ->tooltip(fn (array $record): string => $record['key']),
                TextColumn::make('default_group_label')
                    ->label(__('mksine::admin_menu.columns.default_group'))
                    ->description(fn (array $record): ?string => $record['group_hidden']
                        ? __('mksine::admin_menu.columns.group_hidden')
                        : null),
                TextColumn::make('placement')
                    ->label(__('mksine::admin_menu.columns.placement'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AdminMenuPlacement::from($state)->getLabel())
                    ->color(fn (string $state): string => match (AdminMenuPlacement::from($state)) {
                        AdminMenuPlacement::Default => 'gray',
                        AdminMenuPlacement::Hidden => 'danger',
                        AdminMenuPlacement::Group => 'info',
                        AdminMenuPlacement::Settings => 'warning',
                    }),
                TextColumn::make('target_group_label')
                    ->label(__('mksine::admin_menu.columns.target_group'))
                    ->placeholder('—'),
                TextColumn::make('sort')
                    ->label(__('mksine::admin_menu.columns.sort'))
                    ->state(fn (array $record): int => $record['sort'] ?? $record['default_sort'])
                    ->color(fn (array $record): ?string => $record['sort'] === null ? 'gray' : null),
                TextColumn::make('custom_label')
                    ->label(__('mksine::admin_menu.columns.custom_label'))
                    ->placeholder('—'),
            ])
            ->defaultGroup(
                Group::make('default_group_key')
                    ->label(__('mksine::admin_menu.columns.default_group'))
                    ->getTitleFromRecordUsing(fn (array $record): string => $record['default_group_label'])
                    ->titlePrefixedWithLabel(false),
            )
            ->groupingSettingsHidden()
            ->paginated(false)
            ->recordActions([
                Action::make('toggleHidden')
                    ->label(fn (array $record): string => $record['placement'] === AdminMenuPlacement::Hidden->value
                        ? __('mksine::admin_menu.actions.show')
                        : __('mksine::admin_menu.actions.hide'))
                    ->icon(fn (array $record): Heroicon => $record['placement'] === AdminMenuPlacement::Hidden->value
                        ? Heroicon::OutlinedEye
                        : Heroicon::OutlinedEyeSlash)
                    ->color(fn (array $record): string => $record['placement'] === AdminMenuPlacement::Hidden->value ? 'success' : 'gray')
                    ->visible(fn (array $record): bool => ! $record['is_protected'])
                    ->action(function (array $record): void {
                        $this->toggleItemHidden($record['key']);
                    }),
                Action::make('edit')
                    ->label(__('mksine::admin_menu.actions.edit'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading(fn (array $record): string => $record['label'])
                    ->modalWidth('lg')
                    ->fillForm(fn (array $record): array => [
                        'placement' => $record['placement'],
                        'group' => $record['target_group'],
                        'sort' => $record['sort'],
                        'label' => $record['custom_label'],
                    ])
                    ->schema(fn (array $record): array => $this->itemFormSchema($record))
                    ->action(function (array $record, array $data): void {
                        $this->saveItem($record['key'], $data);
                    }),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('editGroups')
                ->label(__('mksine::admin_menu.actions.edit_groups'))
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('gray')
                ->modalHeading(__('mksine::admin_menu.groups_modal.heading'))
                ->modalDescription(__('mksine::admin_menu.groups_modal.description'))
                ->fillForm(fn (): array => ['groups' => app(AdminMenuOverrides::class)->toArray()['groups']])
                ->schema(fn (): array => $this->groupsFormSchema())
                ->action(function (array $data): void {
                    $this->saveGroups(is_array($data['groups'] ?? null) ? $data['groups'] : []);
                }),
            Action::make('reset')
                ->label(__('mksine::admin_menu.actions.reset'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('mksine::admin_menu.actions.reset_confirm_heading'))
                ->modalDescription(__('mksine::admin_menu.actions.reset_confirm_description'))
                ->action(function (): void {
                    $this->resetMenu();
                }),
        ];
    }

    /**
     * @param  array{placement?: ?string, group?: ?string, sort?: mixed, label?: ?string}  $data
     */
    public function saveItem(string $itemKey, array $data): void
    {
        SuperAdminGate::authorize();

        $placement = AdminMenuPlacement::tryFrom((string) ($data['placement'] ?? '')) ?? AdminMenuPlacement::Default;

        if ($placement->removesFromSidebar() && AdminMenuOverrides::isProtected($itemKey)) {
            $placement = AdminMenuPlacement::Default;
        }

        $overrides = app(AdminMenuOverrides::class)->toArray();
        $overrides['items'][$itemKey] = [
            'placement' => $placement->value,
            'group' => $data['group'] ?? null,
            'sort' => $data['sort'] ?? null,
            'label' => $data['label'] ?? null,
        ];

        $this->persist($overrides);
    }

    /**
     * Hide a visible item, or bring a hidden one back to its default group. Sort and name are kept.
     */
    public function toggleItemHidden(string $itemKey): void
    {
        $override = app(AdminMenuOverrides::class)->toArray()['items'][$itemKey] ?? [];
        $isHidden = ($override['placement'] ?? null) === AdminMenuPlacement::Hidden->value;

        $this->saveItem($itemKey, [
            'placement' => $isHidden ? AdminMenuPlacement::Default->value : AdminMenuPlacement::Hidden->value,
            'sort' => $override['sort'] ?? null,
            'label' => $override['label'] ?? null,
        ]);
    }

    /**
     * @param  array<string, array{sort?: mixed, label?: ?string}>  $groups
     */
    public function saveGroups(array $groups): void
    {
        SuperAdminGate::authorize();

        $overrides = app(AdminMenuOverrides::class)->toArray();
        $overrides['groups'] = $groups;

        $this->persist($overrides);
    }

    public function resetMenu(): void
    {
        SuperAdminGate::authorize();

        app(AdminMenuOverrides::class)->reset();

        Notification::make()
            ->title(__('mksine::admin_menu.notifications.reset'))
            ->success()
            ->send();

        $this->reloadPage();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function persist(array $overrides): void
    {
        try {
            app(AdminMenuOverrides::class)->save($overrides);
        } catch (LengthException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('mksine::admin_menu.notifications.saved'))
            ->success()
            ->send();

        $this->reloadPage();
    }

    /**
     * Full reload so the sidebar (rendered outside this component) shows the new menu.
     */
    protected function reloadPage(): void
    {
        $this->redirect(static::getUrl());
    }

    /**
     * @param  MenuRow  $record
     * @return list<Component>
     */
    protected function itemFormSchema(array $record): array
    {
        $placements = collect(AdminMenuPlacement::cases())
            ->reject(fn (AdminMenuPlacement $placement): bool => $record['is_protected'] && $placement->removesFromSidebar())
            ->mapWithKeys(fn (AdminMenuPlacement $placement): array => [$placement->value => $placement->getLabel()])
            ->all();

        return [
            Select::make('placement')
                ->label(__('mksine::admin_menu.fields.placement'))
                ->options($placements)
                ->default(AdminMenuPlacement::Default->value)
                ->required()
                ->live()
                ->helperText($record['is_protected']
                    ? __('mksine::admin_menu.fields.protected_helper')
                    : __('mksine::admin_menu.fields.placement_helper')),
            Select::make('group')
                ->label(__('mksine::admin_menu.fields.group'))
                ->options(self::groupOptions())
                ->visible(fn (Get $get): bool => $get('placement') === AdminMenuPlacement::Group->value)
                ->required(fn (Get $get): bool => $get('placement') === AdminMenuPlacement::Group->value),
            TextInput::make('sort')
                ->label(__('mksine::admin_menu.fields.sort'))
                ->integer()
                ->placeholder((string) $record['default_sort'])
                ->helperText(__('mksine::admin_menu.fields.sort_helper', ['default' => $record['default_sort']])),
            TextInput::make('label')
                ->label(__('mksine::admin_menu.fields.label'))
                ->maxLength(120)
                ->placeholder($record['label'])
                ->helperText(__('mksine::admin_menu.fields.label_helper')),
        ];
    }

    /**
     * @return list<Component>
     */
    protected function groupsFormSchema(): array
    {
        $schema = [];

        foreach (AdminNavigationGroup::cases() as $group) {
            $default = AdminMenuOverrides::defaultGroupSort($group);

            $schema[] = Fieldset::make($group->getLabel())
                ->columns(2)
                ->schema([
                    TextInput::make("groups.{$group->value}.sort")
                        ->label(__('mksine::admin_menu.groups_modal.sort'))
                        ->integer()
                        ->placeholder(__('mksine::admin_menu.groups_modal.sort_placeholder', ['default' => $default])),
                    TextInput::make("groups.{$group->value}.label")
                        ->label(__('mksine::admin_menu.groups_modal.label'))
                        ->maxLength(120)
                        ->placeholder($group->getLabel()),
                    Toggle::make("groups.{$group->value}.hidden")
                        ->label(__('mksine::admin_menu.groups_modal.hidden'))
                        ->helperText(__('mksine::admin_menu.groups_modal.hidden_helper'))
                        ->columnSpanFull(),
                ]);
        }

        return $schema;
    }

    /**
     * @return array<string, string>
     */
    protected static function groupOptions(): array
    {
        return collect(AdminNavigationGroup::cases())
            ->mapWithKeys(fn (AdminNavigationGroup $group): array => [$group->value => $group->getLabel()])
            ->all();
    }

    /**
     * One row per sidebar item the navigation manager produces before overrides (after the plugin filter).
     *
     * @return array<string, MenuRow>
     */
    protected function menuRows(): array
    {
        $overrides = app(AdminMenuOverrides::class);
        $rows = [];

        foreach ($this->defaultNavigation() as $group) {
            $groupKey = AdminMenuOverrides::groupKey($group);
            $groupLabel = blank($group->getLabel()) ? __('mksine::admin_menu.top_level') : (string) $group->getLabel();

            foreach (collect($group->getItems())->all() as $item) {
                if (! $item instanceof NavigationItem) {
                    continue;
                }

                $key = $item->getKey();
                $override = $overrides->itemOverride($key);
                $targetGroup = $override['group'] ?? null;

                $rows[$key] = [
                    'key' => $key,
                    'label' => $item->getLabel(),
                    'default_group_key' => $groupKey,
                    'default_group_label' => $groupLabel,
                    'default_sort' => $item->getSort(),
                    'placement' => ($override['placement'] ?? AdminMenuPlacement::Default)->value,
                    'target_group' => $targetGroup,
                    'target_group_label' => $targetGroup !== null ? AdminNavigationGroup::from($targetGroup)->getLabel() : null,
                    'sort' => $override['sort'] ?? null,
                    'custom_label' => $override['label'] ?? null,
                    'is_protected' => AdminMenuOverrides::isProtected($key),
                    'group_hidden' => $overrides->groupOverride($groupKey)['hidden'] ?? false,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<NavigationGroup>
     */
    protected function defaultNavigation(): array
    {
        $manager = app(NavigationManager::class);

        return $manager instanceof MksineNavigationManager ? $manager->getDefaultNavigation() : [];
    }

    protected function resolveMenu(): AdminMenuResolution
    {
        $manager = app(NavigationManager::class);

        if (! $manager instanceof MksineNavigationManager) {
            return new AdminMenuResolution([], []);
        }

        return $manager->refresh();
    }

    /**
     * @return list<Component>
     */
    protected function previewComponents(AdminMenuResolution $resolution): array
    {
        $components = [];

        foreach ($resolution->groups as $index => $group) {
            $label = blank($group->getLabel()) ? __('mksine::admin_menu.top_level') : (string) $group->getLabel();

            $components[] = $this->previewFieldset("preview_group_{$index}", $label, collect($group->getItems())->all());
        }

        if ($resolution->settingsItems !== []) {
            $components[] = $this->previewFieldset(
                'preview_settings',
                __('mksine::admin_menu.preview.settings_heading', ['group' => __('mksine::admin_menu.settings_navigation_group')]),
                $resolution->settingsItems,
            );
        }

        return $components;
    }

    /**
     * @param  array<NavigationItem>  $items
     */
    protected function previewFieldset(string $name, string $label, array $items): Fieldset
    {
        $labels = array_values(array_map(fn (NavigationItem $item): string => $item->getLabel(), $items));

        return Fieldset::make($label)
            ->columns(1)
            ->schema([
                TextEntry::make($name)
                    ->hiddenLabel()
                    ->state($labels)
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder(__('mksine::admin_menu.preview.empty')),
            ]);
    }
}
