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
                ->label(__('Key'))
                ->disabled()
                ->helperText(__('Location keys are registered via code and cannot be changed.')),

            TextInput::make('label')
                ->label(__('Label'))
                ->required()
                ->maxLength(255),

            Select::make('menu_id')
                ->label(__('Assigned Menu'))
                ->options(function () {
                    $menus = Menu::query()->orderBy('name')->get();
                    return $menus->pluck('name', 'id')->toArray();
                })
                ->searchable()
                ->preload()
                ->placeholder(__('— No menu assigned —'))
                ->helperText(__('Select the menu to display at this location.')),
        ]);
    }
}
