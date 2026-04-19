<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class MksineHeroDomainComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'mksine_hero_domain';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_mksine_hero_domain');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_mksine_hero_domain');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading_line1')
                ->label(__('mksine::page_builder.mksine_fields.hero_heading_line1'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('heading_line2_prefix')
                ->label(__('mksine::page_builder.mksine_fields.hero_line2_prefix'))
                ->maxLength(120),
            TextInput::make('heading_accent')
                ->label(__('mksine::page_builder.mksine_fields.hero_accent_phrase'))
                ->maxLength(120),
            TextInput::make('heading_after')
                ->label(__('mksine::page_builder.mksine_fields.hero_text_after_accent'))
                ->maxLength(120),
            Textarea::make('subheading')
                ->label(__('mksine::page_builder.mksine_fields.hero_subheading'))
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('cta_label')
                ->label(__('mksine::page_builder.mksine_fields.field_cta_label'))
                ->maxLength(120),
            TextInput::make('cta_url')
                ->label(__('mksine::page_builder.mksine_fields.hero_cta_url'))
                ->url(),
            MediaPicker::make('illustration')
                ->label(__('mksine::page_builder.mksine_fields.hero_illustration'))
                ->isRelation(false)
                ->collection('page_builder')
                ->acceptedFileTypes(['image/*'])
                ->columnSpanFull(),
            TextInput::make('illustration_alt')
                ->label(__('mksine::page_builder.mksine_fields.hero_illustration_alt'))
                ->maxLength(255)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'heading_line1' => 'Your domain.',
            'heading_line2_prefix' => 'Your brand.',
            'heading_accent' => 'Online in minutes.',
            'heading_after' => '',
            'subheading' => 'Register a memorable domain, secure email, and SSL — everything you need to launch with confidence.',
            'cta_label' => 'Search domains',
            'cta_url' => '#',
            'illustration' => null,
            'illustration_alt' => 'Illustration of domain and hosting dashboard',
        ];
    }
}
