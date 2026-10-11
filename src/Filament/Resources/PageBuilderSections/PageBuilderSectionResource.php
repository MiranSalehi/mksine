<?php

namespace Miran\Mksine\Filament\Resources\PageBuilderSections;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Filament\Resources\PageBuilderSections\Pages\CreatePageBuilderSection;
use Miran\Mksine\Filament\Resources\PageBuilderSections\Pages\EditPageBuilderSection;
use Miran\Mksine\Filament\Resources\PageBuilderSections\Pages\ListPageBuilderSections;
use Miran\Mksine\Filament\Resources\PageBuilderSections\Schemas\PageBuilderSectionForm;
use Miran\Mksine\Filament\Resources\PageBuilderSections\Tables\PageBuilderSectionTable;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Models\PageBuilderSection;

class PageBuilderSectionResource extends Resource
{
    protected static ?string $model = PageBuilderSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('mksine::page_builder.sections_navigation');
    }

    public static function getModelLabel(): string
    {
        return __('mksine::page_builder.section_ref_name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::page_builder.sections_navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminSidebarNavigation::contentGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return PageBuilderSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageBuilderSectionTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageBuilderSections::route('/'),
            'create' => CreatePageBuilderSection::route('/create'),
            'edit' => EditPageBuilderSection::route('/{record}/edit'),
        ];
    }
}
