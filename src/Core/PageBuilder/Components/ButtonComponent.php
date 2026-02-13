<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class ButtonComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'button';
    }

    public static function getName(): string
    {
        return __('Button');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-cursor-arrow-ripple';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_INTERACTIVE;
    }

    public static function getDescription(): string
    {
        return __('Add a call-to-action button.');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('text')
                ->label(__('Button Text'))
                ->required()
                ->maxLength(100)
                ->placeholder(__('Click here')),
            TextInput::make('url')
                ->label(__('Link URL'))
                ->required()
                ->url()
                ->placeholder('https://'),
            Select::make('style')
                ->label(__('Style'))
                ->options([
                    'primary' => __('Primary'),
                    'secondary' => __('Secondary'),
                    'outline' => __('Outline'),
                    'ghost' => __('Ghost'),
                ])
                ->default('primary')
                ->native(false),
            Select::make('size')
                ->label(__('Size'))
                ->options([
                    'sm' => __('Small'),
                    'md' => __('Medium'),
                    'lg' => __('Large'),
                ])
                ->default('md')
                ->native(false),
            Select::make('alignment')
                ->label(__('Alignment'))
                ->options([
                    'left' => __('Left'),
                    'center' => __('Center'),
                    'right' => __('Right'),
                ])
                ->default('left')
                ->native(false),
            Toggle::make('new_tab')
                ->label(__('Open in New Tab'))
                ->default(false),
            Toggle::make('full_width')
                ->label(__('Full Width'))
                ->default(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'text' => '',
            'url' => '',
            'style' => 'primary',
            'size' => 'md',
            'alignment' => 'left',
            'new_tab' => false,
            'full_width' => false,
        ];
    }
}
