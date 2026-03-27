<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\MenuLocations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Miran\Mksine\Models\Menu;

class MenuLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->label(__('mksine::menu_locations.key'))
                ->disabled()
                ->helperText(__('mksine::menu_locations.key_helper')),

            TextInput::make('label')
                ->label(__('mksine::menu_locations.label'))
                ->required()
                ->maxLength(255),

            Select::make('menu_id')
                ->label(__('mksine::menu_locations.assigned_menu'))
                ->options(function () {
                    $menus = Menu::query()->orderBy('name')->get();
                    return $menus->pluck('name', 'id')->toArray();
                })
                ->searchable()
                ->preload()
                ->placeholder(__('mksine::menu_locations.no_menu_assigned'))
                ->helperText(__('mksine::menu_locations.assigned_menu_helper')),
        ]);
    }
}
