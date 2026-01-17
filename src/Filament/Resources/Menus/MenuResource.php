<?php

namespace Miran\Mksine\Filament\Resources\Menus;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Filament\Resources\Menus\Pages\CreateMenu;
use Miran\Mksine\Filament\Resources\Menus\Pages\EditMenu;
use Miran\Mksine\Filament\Resources\Menus\Pages\ListMenus;
use Miran\Mksine\Filament\Resources\Menus\Schemas\MenuForm;
use Miran\Mksine\Filament\Resources\Menus\Tables\MenuTable;
use Miran\Mksine\Models\Menu;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationLabel = 'Menus';

    protected static ?string $modelLabel = 'Menu';

    protected static ?string $pluralModelLabel = 'Menus';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Appearance');
    }

    public static function form(Schema $schema): Schema
    {
        return MenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuTable::configure($table);
    }

    public static function getRelations(): array
    {
        $relations = [
            //
        ];

        // Apply resource hooks
        $resourceHookManager = app(ResourceHookManager::class);

        return $resourceHookManager->applyRelations('menu.resource', $relations);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
