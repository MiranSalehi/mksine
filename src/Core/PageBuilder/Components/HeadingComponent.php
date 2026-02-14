<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class HeadingComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'heading';
    }

    public static function getName(): string
    {
        return __('Heading');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-h1';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_CONTENT;
    }

    public static function getDescription(): string
    {
        return __('Add a heading or title to your page.');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('text')
                ->label(__('Heading Text'))
                ->required()
                ->maxLength(255)
                ->placeholder(__('Enter heading text...')),
            Select::make('level')
                ->label(__('Heading Level'))
                ->options([
                    'h1' => 'H1 - Main Title',
                    'h2' => 'H2 - Section Title',
                    'h3' => 'H3 - Subsection',
                    'h4' => 'H4 - Small Heading',
                    'h5' => 'H5 - Minor Heading',
                    'h6' => 'H6 - Smallest',
                ])
                ->default('h2')
                ->required()
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
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'text' => '',
            'level' => 'h2',
            'alignment' => 'left',
        ];
    }
}
