<?php

namespace Miran\Mksine\Filament\Resources\Users;

use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Miran\Mksine\Filament\Resources\Users\Pages\CreateUser;
use Miran\Mksine\Filament\Resources\Users\Pages\EditUser;
use Miran\Mksine\Filament\Resources\Users\Pages\ListUsers;
use Miran\Mksine\Filament\Resources\Users\Schemas\UserForm;
use Miran\Mksine\Filament\Resources\Users\Tables\UserTable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('mksine::users.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('mksine::users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::users.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('mksine::common.access_control');
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
