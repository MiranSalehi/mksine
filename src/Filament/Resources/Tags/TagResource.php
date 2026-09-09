<?php

namespace Miran\Mksine\Filament\Resources\Tags;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Filament\Resources\Tags\Pages\CreateTag;
use Miran\Mksine\Filament\Resources\Tags\Pages\EditTag;
use Miran\Mksine\Filament\Resources\Tags\Pages\ListTags;
use Miran\Mksine\Filament\Resources\Tags\Schemas\TagForm;
use Miran\Mksine\Filament\Resources\Tags\Tables\TagTable;
use Miran\Mksine\Models\Tag;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    public static function getNavigationLabel(): string
    {
        return __('mksine::tags.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('mksine::tags.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::tags.plural_model_label');
    }

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminSidebarNavigation::contentGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return TagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagTable::configure($table);
    }

    public static function getRelations(): array
    {
        $relations = [
            //
        ];

        $resourceHookManager = app(ResourceHookManager::class);

        return $resourceHookManager->applyRelations('tag.resource', $relations);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
}
