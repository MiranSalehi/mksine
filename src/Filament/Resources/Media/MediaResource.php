<?php

namespace Miran\Mksine\Filament\Resources\Media;

use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Filament\Resources\Media\Pages\CreateMedia;
use Miran\Mksine\Filament\Resources\Media\Pages\EditMedia;
use Miran\Mksine\Filament\Resources\Media\Pages\ListMedia;
use Miran\Mksine\Filament\Resources\Media\Schemas\MediaForm;
use Miran\Mksine\Filament\Resources\Media\Tables\MediaTable;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Models\Media;

use function Filament\Support\original_request;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return AdminSidebarNavigation::usesShopSidebar()
            ? AdminSidebarNavigation::group(AdminSidebarNavigation::GROUP_MEDIA)
            : AdminSidebarNavigation::contentGroup();
    }

    public static function getNavigationLabel(): string
    {
        return __('mksine::media.navigation_label');
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        if (! AdminSidebarNavigation::usesShopSidebar()) {
            return parent::getNavigationItems();
        }

        if (! static::hasPage('index')) {
            return [];
        }

        $items = [
            NavigationItem::make(__('mksine::media.navigation_library'))
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.index')
                    || (original_request()->routeIs(static::getRouteBaseName().'.edit') && ! original_request()->routeIs(static::getRouteBaseName().'.create')))
                ->sort(10)
                ->url(static::getUrl('index')),
        ];

        if (static::hasPage('create') && static::can('create')) {
            $items[] = NavigationItem::make(__('mksine::media.navigation_create'))
                ->group(static::getNavigationGroup())
                ->icon(Heroicon::OutlinedPlusCircle)
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.create'))
                ->sort(11)
                ->url(static::getUrl('create'));
        }

        return $items;
    }

    public static function getModelLabel(): string
    {
        return __('mksine::media.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::media.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return MediaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
