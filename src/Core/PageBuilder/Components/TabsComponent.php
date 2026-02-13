<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class TabsComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'tabs';
    }

    public static function getName(): string
    {
        return __('Tabs');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('Tabbed content sections for organized information.');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('style')
                ->label(__('Tab Style'))
                ->options([
                    'underline' => __('Underline'),
                    'pills' => __('Pills'),
                    'boxed' => __('Boxed'),
                    'buttons' => __('Buttons'),
                ])
                ->default('underline')
                ->native(false),
            Select::make('alignment')
                ->label(__('Tab Alignment'))
                ->options([
                    'left' => __('Left'),
                    'center' => __('Center'),
                    'right' => __('Right'),
                    'full' => __('Full Width'),
                ])
                ->default('left')
                ->native(false),
            Select::make('orientation')
                ->label(__('Orientation'))
                ->options([
                    'horizontal' => __('Horizontal'),
                    'vertical' => __('Vertical'),
                ])
                ->default('horizontal')
                ->native(false),
            Repeater::make('tabs')
                ->label(__('Tabs'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('Tab Title'))
                        ->required()
                        ->maxLength(100),
                    TextInput::make('icon')
                        ->label(__('Icon (optional)'))
                        ->placeholder('heroicon-o-home')
                        ->helperText(__('Heroicon name')),
                    RichEditor::make('content')
                        ->label(__('Tab Content'))
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'link',
                            'orderedList',
                            'bulletList',
                            'h2',
                            'h3',
                        ])
                        ->columnSpanFull(),
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
            'style' => 'underline',
            'alignment' => 'left',
            'orientation' => 'horizontal',
            'tabs' => [
                ['title' => 'Tab 1', 'icon' => '', 'content' => ''],
                ['title' => 'Tab 2', 'icon' => '', 'content' => ''],
                ['title' => 'Tab 3', 'icon' => '', 'content' => ''],
            ],
        ];
    }
}
