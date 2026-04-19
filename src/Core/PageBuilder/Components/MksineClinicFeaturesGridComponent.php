<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class MksineClinicFeaturesGridComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'mksine_clinic_features_grid';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_mksine_clinic_features_grid');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_mksine_clinic_features_grid');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('text_direction')
                ->label(__('mksine::page_builder.mksine_fields.text_direction'))
                ->options([
                    'auto' => __('mksine::page_builder.mksine_fields.text_direction_auto'),
                    'ltr' => __('mksine::page_builder.mksine_fields.direction_ltr'),
                    'rtl' => __('mksine::page_builder.mksine_fields.direction_rtl'),
                ])
                ->default('auto')
                ->native(false),
            TextInput::make('badge')
                ->label(__('mksine::page_builder.mksine_fields.field_badge'))
                ->maxLength(120)
                ->columnSpanFull(),
            TextInput::make('heading_prefix')
                ->label(__('mksine::page_builder.mksine_fields.clinic_heading_prefix'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('heading_accent')
                ->label(__('mksine::page_builder.mksine_fields.clinic_heading_accent'))
                ->maxLength(120)
                ->columnSpanFull(),
            Textarea::make('subheading')
                ->label(__('mksine::page_builder.component_labels.subtitle'))
                ->rows(3)
                ->columnSpanFull(),
            Repeater::make('cards')
                ->label(__('mksine::page_builder.mksine_fields.clinic_cards'))
                ->schema([
                    TextInput::make('gradient')
                        ->label(__('mksine::page_builder.mksine_fields.clinic_gradient_tw'))
                        ->placeholder(__('mksine::page_builder.mksine_fields.clinic_gradient_placeholder'))
                        ->required(),
                    TextInput::make('icon')
                        ->label(__('mksine::page_builder.mksine_fields.clinic_icon_name'))
                        ->helperText(__('mksine::page_builder.mksine_fields.clinic_icon_help'))
                        ->required()
                        ->maxLength(64),
                    TextInput::make('title')
                        ->label(__('mksine::page_builder.component_labels.title'))
                        ->required()
                        ->maxLength(200),
                    Textarea::make('body')
                        ->label(__('mksine::page_builder.mksine_fields.field_body'))
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->defaultItems(6)
                ->minItems(1)
                ->maxItems(24)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'text_direction' => 'auto',
            'badge' => 'Why teams choose us',
            'heading_prefix' => 'Powerful tools for ',
            'heading_accent' => 'modern clinics',
            'subheading' => 'Everything you need to run scheduling, records, and patient communication in one place.',
            'cards' => [
                ['gradient' => 'from-blue-500 to-blue-600', 'icon' => 'users', 'title' => 'Electronic records', 'body' => 'Structured charts, attachments, and history at a glance.'],
                ['gradient' => 'from-emerald-500 to-emerald-600', 'icon' => 'calendar', 'title' => 'Smart scheduling', 'body' => 'Reduce no-shows with reminders and online booking.'],
                ['gradient' => 'from-violet-500 to-violet-600', 'icon' => 'chart-column', 'title' => 'Reports', 'body' => 'Export-ready insights for revenue and visits.'],
                ['gradient' => 'from-yellow-500 to-yellow-600', 'icon' => 'clipboard-list', 'title' => 'Inventory', 'body' => 'Track supplies with low-stock alerts.'],
                ['gradient' => 'from-purple-500 to-purple-600', 'icon' => 'credit-card', 'title' => 'Billing', 'body' => 'Invoices, receipts, and payment tracking built in.'],
                ['gradient' => 'from-pink-500 to-pink-600', 'icon' => 'settings', 'title' => 'Automation', 'body' => 'Rules that save hours every week.'],
            ],
        ];
    }
}
