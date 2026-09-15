<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;

class VideoComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'video';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_video');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-video-camera';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_MEDIA;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_video');
    }

    public static function getSchema(): array
    {
        return [
            MediaPicker::make('video')
                ->label(__('mksine::page_builder.component_labels.field_video'))
                ->required()
                ->isRelation(false)
                ->collection('page_builder')
                ->acceptedFileTypes(['video/*'])
                ->columnSpanFull(),
            TextInput::make('caption')
                ->label(__('mksine::page_builder.component_labels.caption'))
                ->placeholder(__('mksine::page_builder.component_labels.optional_caption'))
                ->maxLength(500),
            Toggle::make('controls')
                ->label(__('mksine::page_builder.component_labels.media_controls'))
                ->default(true),
            Toggle::make('autoplay')
                ->label(__('mksine::page_builder.component_labels.autoplay'))
                ->helperText(__('mksine::page_builder.component_labels.video_autoplay_hint'))
                ->default(false),
            Toggle::make('loop')
                ->label(__('mksine::page_builder.component_labels.media_loop'))
                ->default(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'video' => null,
            'caption' => '',
            'controls' => true,
            'autoplay' => false,
            'loop' => false,
        ];
    }
}
