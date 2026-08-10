<?php

namespace Miran\Mksine\Filament\Resources\Categories;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Filament\Resources\Categories\Pages\CreateCategory;
use Miran\Mksine\Filament\Resources\Categories\Pages\EditCategory;
use Miran\Mksine\Filament\Resources\Categories\Pages\ListCategories;
use Miran\Mksine\Filament\Resources\Categories\Schemas\CategoryForm;
use Miran\Mksine\Filament\Resources\Categories\Tables\CategoryTable;
use Miran\Mksine\Models\Category;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    public static function getNavigationLabel(): string
    {
        return __('mksine::categories.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('mksine::categories.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::categories.plural_model_label');
    }

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminSidebarNavigation::contentGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoryTable::configure($table);
    }

    public static function getRelations(): array
    {
        $relations = [
            //
        ];

        // Apply resource hooks
        $resourceHookManager = app(ResourceHookManager::class);

        return $resourceHookManager->applyRelations('category.resource', $relations);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
