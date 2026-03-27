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
        return __('mksine::page_builder.component_labels.name_image');
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
        return __('mksine::page_builder.component_labels.desc_image');
    }

    public static function getSchema(): array
    {
        return [
            MediaPicker::make('image')
                ->label(__('mksine::page_builder.component_labels.field_image'))
                ->required()
                ->isRelation(false)
                ->collection('page_builder')
                ->acceptedFileTypes(['image/*'])
                ->columnSpanFull(),
            TextInput::make('alt')
                ->label(__('mksine::page_builder.component_labels.alt_text'))
                ->placeholder(__('mksine::page_builder.component_labels.describe_image'))
                ->maxLength(255),
            TextInput::make('caption')
                ->label(__('mksine::page_builder.component_labels.caption'))
                ->placeholder(__('mksine::page_builder.component_labels.optional_caption'))
                ->maxLength(500),
            Select::make('size')
                ->label(__('mksine::page_builder.component_labels.size'))
                ->options([
                    'small' => __('mksine::page_builder.component_labels.small'),
                    'medium' => __('mksine::page_builder.component_labels.medium'),
                    'large' => __('mksine::page_builder.component_labels.large'),
                    'full' => __('mksine::page_builder.component_labels.full_width'),
                ])
                ->default('large')
                ->native(false),
            Select::make('alignment')
                ->label(__('mksine::page_builder.component_labels.alignment'))
                ->options([
                    'left' => __('mksine::page_builder.component_labels.left'),
                    'center' => __('mksine::page_builder.component_labels.center'),
                    'right' => __('mksine::page_builder.component_labels.right'),
                ])
                ->default('center')
                ->native(false),
            Toggle::make('rounded')
                ->label(__('mksine::page_builder.component_labels.rounded_corners'))
                ->default(false),
            Toggle::make('shadow')
                ->label(__('mksine::page_builder.component_labels.drop_shadow'))
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
