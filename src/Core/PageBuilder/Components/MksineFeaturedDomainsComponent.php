<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class MksineFeaturedDomainsComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'mksine_featured_domains';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_mksine_featured_domains');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-tag';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_mksine_featured_domains');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('mksine::page_builder.mksine_fields.field_section_title'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('view_all_label')
                ->label(__('mksine::page_builder.mksine_fields.feat_view_all_label'))
                ->maxLength(120),
            TextInput::make('view_all_url')
                ->label(__('mksine::page_builder.mksine_fields.feat_view_all_url'))
                ->url(),
            Repeater::make('items')
                ->label(__('mksine::page_builder.mksine_fields.feat_tld_cards'))
                ->schema([
                    TextInput::make('tld')
                        ->label(__('mksine::page_builder.mksine_fields.feat_tld'))
                        ->placeholder('.com')
                        ->required()
                        ->maxLength(32),
                    TextInput::make('slug')
                        ->label(__('mksine::page_builder.mksine_fields.feat_logo_key'))
                        ->helperText(__('mksine::page_builder.mksine_fields.feat_logo_key_help'))
                        ->required()
                        ->maxLength(32),
                    TextInput::make('category')
                        ->label(__('mksine::page_builder.mksine_fields.feat_category_subtitle'))
                        ->maxLength(120),
                    TextInput::make('price')
                        ->label(__('mksine::page_builder.mksine_fields.feat_price'))
                        ->maxLength(32),
                    TextInput::make('original_price')
                        ->label(__('mksine::page_builder.mksine_fields.feat_original_price'))
                        ->maxLength(32),
                    TextInput::make('discount_percent')
                        ->label(__('mksine::page_builder.mksine_fields.feat_discount_percent'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    TextInput::make('href')
                        ->label(__('mksine::page_builder.component_labels.link_url'))
                        ->url(),
                    MediaPicker::make('logo')
                        ->label(__('mksine::page_builder.mksine_fields.feat_logo_image_optional'))
                        ->isRelation(false)
                        ->collection('page_builder')
                        ->acceptedFileTypes(['image/*']),
                ])
                ->defaultItems(6)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['tld'] ?? null)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'title' => 'Featured extensions',
            'view_all_label' => 'View all domains',
            'view_all_url' => '#',
            'items' => [
                ['tld' => '.es', 'slug' => 'es', 'category' => 'Spain / EU', 'price' => '€6.95', 'original_price' => null, 'discount_percent' => null, 'href' => '#', 'logo' => null],
                ['tld' => '.com', 'slug' => 'com', 'category' => 'Global classic', 'price' => '€15.95', 'original_price' => null, 'discount_percent' => null, 'href' => '#', 'logo' => null],
                ['tld' => '.net', 'slug' => 'net', 'category' => 'Networks', 'price' => '€4.20', 'original_price' => '€14.95', 'discount_percent' => 71, 'href' => '#', 'logo' => null],
                ['tld' => '.online', 'slug' => 'online', 'category' => 'Bold & modern', 'price' => '€2.95', 'original_price' => '€30.95', 'discount_percent' => 90, 'href' => '#', 'logo' => null],
                ['tld' => '.site', 'slug' => 'site', 'category' => 'Simple & clear', 'price' => '€37.45', 'original_price' => null, 'discount_percent' => null, 'href' => '#', 'logo' => null],
                ['tld' => '.org', 'slug' => 'org', 'category' => 'Organizations', 'price' => '€8.45', 'original_price' => '€12.95', 'discount_percent' => 34, 'href' => '#', 'logo' => null],
            ],
        ];
    }

    public static function validate(array $data): array
    {
        foreach ($data['items'] ?? [] as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (isset($row['discount_percent']) && $row['discount_percent'] === '') {
                $data['items'][$i]['discount_percent'] = null;
            }
        }

        return $data;
    }
}
