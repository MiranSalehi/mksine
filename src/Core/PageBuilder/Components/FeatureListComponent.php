<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class FeatureListComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'features';
    }

    public static function getName(): string
    {
        return __('Feature List');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-list-bullet';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_CONTENT;
    }

    public static function getDescription(): string
    {
        return __('Display a grid of features with icons, titles, and descriptions.');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading')
                ->label(__('Section Heading'))
                ->maxLength(255)
                ->placeholder(__('Our Features'))
                ->columnSpanFull(),
            Textarea::make('subheading')
                ->label(__('Section Subheading'))
                ->rows(2)
                ->maxLength(500)
                ->columnSpanFull(),
            Select::make('columns')
                ->label(__('Columns'))
                ->options([
                    2 => __('2 Columns'),
                    3 => __('3 Columns'),
                    4 => __('4 Columns'),
                ])
                ->default(3)
                ->native(false),
            Select::make('style')
                ->label(__('Card Style'))
                ->options([
                    'simple' => __('Simple'),
                    'bordered' => __('Bordered'),
                    'shadowed' => __('Shadowed'),
                    'filled' => __('Filled'),
                ])
                ->default('simple')
                ->native(false),
            Select::make('icon_style')
                ->label(__('Icon Style'))
                ->options([
                    'circle' => __('Circle'),
                    'square' => __('Square'),
                    'none' => __('No Background'),
                ])
                ->default('circle')
                ->native(false),
            Repeater::make('features')
                ->label(__('Features'))
                ->schema([
                    TextInput::make('icon')
                        ->label(__('Icon'))
                        ->placeholder('heroicon-o-star')
                        ->helperText(__('Heroicon name (e.g., heroicon-o-star)')),
                    TextInput::make('title')
                        ->label(__('Title'))
                        ->required()
                        ->maxLength(100),
                    Textarea::make('description')
                        ->label(__('Description'))
                        ->rows(2)
                        ->maxLength(300),
                    TextInput::make('link')
                        ->label(__('Link (optional)'))
                        ->url()
                        ->placeholder('https://'),
                ])
                ->columns(2)
                ->defaultItems(3)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'heading' => '',
            'subheading' => '',
            'columns' => 3,
            'style' => 'simple',
            'icon_style' => 'circle',
            'features' => [
                ['icon' => 'heroicon-o-bolt', 'title' => 'Fast', 'description' => '', 'link' => ''],
                ['icon' => 'heroicon-o-shield-check', 'title' => 'Secure', 'description' => '', 'link' => ''],
                ['icon' => 'heroicon-o-heart', 'title' => 'Reliable', 'description' => '', 'link' => ''],
            ],
        ];
    }
}
