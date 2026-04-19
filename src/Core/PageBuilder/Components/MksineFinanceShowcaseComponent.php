<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class MksineFinanceShowcaseComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'mksine_finance_showcase';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_mksine_finance_showcase');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chart-bar-square';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_mksine_finance_showcase');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading_prefix')
                ->label(__('mksine::page_builder.mksine_fields.fin_heading_prefix'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('heading_accent')
                ->label(__('mksine::page_builder.mksine_fields.fin_accent_word'))
                ->maxLength(120)
                ->columnSpanFull(),
            TextInput::make('heading_suffix')
                ->label(__('mksine::page_builder.mksine_fields.fin_heading_suffix'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('p1_before')
                ->label(__('mksine::page_builder.mksine_fields.fin_p1_before'))
                ->columnSpanFull(),
            TextInput::make('p1_highlight')
                ->label(__('mksine::page_builder.mksine_fields.fin_p1_highlight'))
                ->columnSpanFull(),
            TextInput::make('p1_after')
                ->label(__('mksine::page_builder.mksine_fields.fin_p1_after'))
                ->columnSpanFull(),
            TextInput::make('p2_before')
                ->label(__('mksine::page_builder.mksine_fields.fin_p2_before'))
                ->columnSpanFull(),
            TextInput::make('p2_highlight')
                ->label(__('mksine::page_builder.mksine_fields.fin_p2_highlight'))
                ->columnSpanFull(),
            TextInput::make('p2_after')
                ->label(__('mksine::page_builder.mksine_fields.fin_p2_after'))
                ->columnSpanFull(),
            TextInput::make('social_caption')
                ->label(__('mksine::page_builder.mksine_fields.fin_social_caption'))
                ->columnSpanFull(),
            TextInput::make('social_count')
                ->label(__('mksine::page_builder.mksine_fields.fin_social_stat'))
                ->maxLength(120),
            Textarea::make('trust_line')
                ->label(__('mksine::page_builder.mksine_fields.fin_trust_line'))
                ->rows(2)
                ->columnSpanFull(),
            TextInput::make('cta_primary_label')
                ->label(__('mksine::page_builder.mksine_fields.fin_cta_primary_label'))
                ->maxLength(120),
            TextInput::make('cta_primary_url')
                ->label(__('mksine::page_builder.mksine_fields.fin_cta_primary_url'))
                ->url(),
            TextInput::make('cta_secondary_label')
                ->label(__('mksine::page_builder.mksine_fields.fin_cta_secondary_label'))
                ->maxLength(120),
            TextInput::make('cta_secondary_url')
                ->label(__('mksine::page_builder.mksine_fields.fin_cta_secondary_url'))
                ->url(),
            TextInput::make('mock_chart_caption')
                ->label(__('mksine::page_builder.mksine_fields.fin_mock_chart_caption'))
                ->maxLength(120),
            Repeater::make('avatars')
                ->label(__('mksine::page_builder.mksine_fields.fin_avatar_stack'))
                ->schema([
                    TextInput::make('from')
                        ->label(__('mksine::page_builder.mksine_fields.fin_gradient_from'))
                        ->placeholder(__('mksine::page_builder.mksine_fields.fin_gradient_from_placeholder'))
                        ->required(),
                    TextInput::make('to')
                        ->label(__('mksine::page_builder.mksine_fields.fin_gradient_to'))
                        ->placeholder(__('mksine::page_builder.mksine_fields.fin_gradient_to_placeholder'))
                        ->required(),
                    TextInput::make('letter')
                        ->label(__('mksine::page_builder.mksine_fields.fin_letter'))
                        ->maxLength(2)
                        ->required(),
                ])
                ->defaultItems(5)
                ->reorderable()
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'heading_prefix' => 'Run your clinic finances ',
            'heading_accent' => 'without the chaos.',
            'heading_suffix' => '',
            'p1_before' => 'Invoices, payouts, and taxes in one ',
            'p1_highlight' => 'clear dashboard',
            'p1_after' => ' — built for busy teams.',
            'p2_before' => 'Automate reminders and get paid faster with ',
            'p2_highlight' => 'smart follow-ups',
            'p2_after' => ' your patients actually notice.',
            'social_caption' => 'Trusted by modern practices worldwide',
            'social_count' => '12,400+ teams',
            'trust_line' => 'No credit card required to explore the demo.',
            'cta_primary_label' => 'Start free trial',
            'cta_primary_url' => '#',
            'cta_secondary_label' => 'Talk to sales',
            'cta_secondary_url' => '#',
            'mock_chart_caption' => 'Monthly revenue',
            'avatars' => [
                ['from' => 'from-violet-500', 'to' => 'to-purple-600', 'letter' => 'A'],
                ['from' => 'from-indigo-500', 'to' => 'to-blue-600', 'letter' => 'B'],
                ['from' => 'from-fuchsia-500', 'to' => 'to-pink-600', 'letter' => 'C'],
                ['from' => 'from-amber-500', 'to' => 'to-orange-600', 'letter' => 'D'],
                ['from' => 'from-emerald-500', 'to' => 'to-teal-600', 'letter' => 'E'],
            ],
        ];
    }
}
