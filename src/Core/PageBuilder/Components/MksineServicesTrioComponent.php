<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class MksineServicesTrioComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'mksine_services_trio';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_mksine_services_trio');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_mksine_services_trio');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('section_title')
                ->label(__('mksine::page_builder.mksine_fields.field_section_title'))
                ->maxLength(255)
                ->columnSpanFull(),
            Repeater::make('cards')
                ->label(__('mksine::page_builder.mksine_fields.svc_service_cards'))
                ->schema([
                    MediaPicker::make('image')
                        ->label(__('mksine::page_builder.component_labels.field_image'))
                        ->isRelation(false)
                        ->collection('page_builder')
                        ->acceptedFileTypes(['image/*', 'image/svg+xml']),
                    TextInput::make('image_alt')
                        ->label(__('mksine::page_builder.mksine_fields.field_image_alt'))
                        ->maxLength(255),
                    TextInput::make('title')
                        ->label(__('mksine::page_builder.component_labels.title'))
                        ->required()
                        ->maxLength(200),
                    Textarea::make('body')
                        ->label(__('mksine::page_builder.mksine_fields.field_body'))
                        ->rows(3)
                        ->maxLength(600),
                    TextInput::make('cta_label')
                        ->label(__('mksine::page_builder.mksine_fields.field_cta_label'))
                        ->maxLength(80),
                    TextInput::make('url')
                        ->label(__('mksine::page_builder.mksine_fields.svc_card_url'))
                        ->url(),
                ])
                ->defaultItems(3)
                ->minItems(1)
                ->maxItems(6)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'section_title' => 'Everything to get you online',
            'cards' => [
                [
                    'image' => null,
                    'image_alt' => 'Web hosting icon',
                    'title' => 'Fast hosting',
                    'body' => 'SSD storage, daily backups, and a control panel that stays out of your way.',
                    'cta_label' => 'View plans',
                    'url' => '#',
                ],
                [
                    'image' => null,
                    'image_alt' => 'Professional email icon',
                    'title' => 'Pro email',
                    'body' => 'Branded inboxes with spam protection and sync across all your devices.',
                    'cta_label' => 'Get email',
                    'url' => '#',
                ],
                [
                    'image' => null,
                    'image_alt' => 'SSL security icon',
                    'title' => 'SSL included',
                    'body' => 'Encrypt visitor traffic and boost trust with certificates on every site.',
                    'cta_label' => 'Learn more',
                    'url' => '#',
                ],
            ],
        ];
    }
}
