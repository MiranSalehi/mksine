<?php

namespace Miran\Mksine\Filament\Resources\Pages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Filament\Resources\Pages\Pages\CreatePage;
use Miran\Mksine\Filament\Resources\Pages\Pages\EditPage;
use Miran\Mksine\Filament\Resources\Pages\Pages\ListPages;
use Miran\Mksine\Filament\Resources\Pages\Schemas\PageForm;
use Miran\Mksine\Filament\Resources\Pages\Tables\PageTable;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    public static function getNavigationLabel(): string
    {
        return __('mksine::pages.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('mksine::pages.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::pages.plural_model_label');
    }

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocument;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('mksine::common.content');
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageTable::configure($table);
    }

    public static function getRelations(): array
    {
        $relations = [
            //
        ];

        // Apply resource hooks
        $resourceHookManager = app(ResourceHookManager::class);

        return $resourceHookManager->applyRelations('page.resource', $relations);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
