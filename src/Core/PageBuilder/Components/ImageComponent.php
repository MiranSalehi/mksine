<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class ImageComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'image';
    }

    public static function getName(): string
    {
        return __('Image');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_MEDIA;
    }

    public static function getDescription(): string
    {
        return __('Add an image from the media library.');
    }

    public static function getSchema(): array
    {
        return [
            MediaPicker::make('image')
                ->label(__('Image'))
                ->required()
                ->isRelation(false)
                ->collection('page_builder')
                ->acceptedFileTypes(['image/*'])
                ->columnSpanFull(),
            TextInput::make('alt')
                ->label(__('Alt Text'))
                ->placeholder(__('Describe the image for accessibility'))
                ->maxLength(255),
            TextInput::make('caption')
                ->label(__('Caption'))
                ->placeholder(__('Optional image caption'))
                ->maxLength(500),
            Select::make('size')
                ->label(__('Size'))
                ->options([
                    'small' => __('Small'),
                    'medium' => __('Medium'),
                    'large' => __('Large'),
                    'full' => __('Full Width'),
                ])
                ->default('large')
                ->native(false),
            Select::make('alignment')
                ->label(__('Alignment'))
                ->options([
                    'left' => __('Left'),
                    'center' => __('Center'),
                    'right' => __('Right'),
                ])
                ->default('center')
                ->native(false),
            Toggle::make('rounded')
                ->label(__('Rounded Corners'))
                ->default(false),
            Toggle::make('shadow')
                ->label(__('Drop Shadow'))
                ->default(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'image' => null,
            'alt' => '',
            'caption' => '',
            'size' => 'large',
            'alignment' => 'center',
            'rounded' => false,
            'shadow' => false,
        ];
    }
}
