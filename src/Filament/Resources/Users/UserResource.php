<?php

namespace Miran\Mksine\Filament\Resources\Users;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Filament\Resources\Users\Pages\CreateUser;
use Miran\Mksine\Filament\Resources\Users\Pages\EditUser;
use Miran\Mksine\Filament\Resources\Users\Pages\ListUsers;
use Miran\Mksine\Filament\Resources\Users\Schemas\UserForm;
use Miran\Mksine\Filament\Resources\Users\Tables\UserTable;

class UserResource extends Resource
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 10;

    public static function getModel(): string
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> */
        return config('mksine.user_model', \App\Models\User::class);
    }

    public static function getNavigationLabel(): string
    {
        return AdminSidebarNavigation::usesShopSidebar()
            ? __('mksine::users.navigation_list')
            : __('mksine::users.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('mksine::users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::users.plural_model_label');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminSidebarNavigation::usesShopSidebar()
            ? AdminSidebarNavigation::case(AdminSidebarNavigation::GROUP_USERS)
            : AdminSidebarNavigation::accessControlGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
