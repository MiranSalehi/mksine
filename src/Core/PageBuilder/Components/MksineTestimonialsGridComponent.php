<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class MksineTestimonialsGridComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'mksine_testimonials_grid';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_mksine_testimonials_grid');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_mksine_testimonials_grid');
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
            TextInput::make('title_prefix')
                ->label(__('mksine::page_builder.mksine_fields.test_title_prefix'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('title_accent')
                ->label(__('mksine::page_builder.mksine_fields.test_title_accent'))
                ->maxLength(120)
                ->columnSpanFull(),
            Textarea::make('subheading')
                ->label(__('mksine::page_builder.component_labels.subtitle'))
                ->rows(2)
                ->columnSpanFull(),
            Textarea::make('aside')
                ->label(__('mksine::page_builder.mksine_fields.test_side_note'))
                ->rows(2)
                ->columnSpanFull(),
            Repeater::make('items')
                ->label(__('mksine::page_builder.mksine_fields.test_testimonials'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('mksine::page_builder.mksine_fields.field_name'))
                        ->required()
                        ->maxLength(120),
                    TextInput::make('city')
                        ->label(__('mksine::page_builder.mksine_fields.test_city_role'))
                        ->maxLength(120),
                    Textarea::make('quote')
                        ->label(__('mksine::page_builder.mksine_fields.test_quote'))
                        ->rows(4)
                        ->required()
                        ->maxLength(800),
                    TextInput::make('gradient')
                        ->label(__('mksine::page_builder.mksine_fields.test_accent_gradient'))
                        ->placeholder(__('mksine::page_builder.mksine_fields.test_gradient_placeholder'))
                        ->required(),
                ])
                ->defaultItems(6)
                ->minItems(1)
                ->maxItems(12)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'text_direction' => 'auto',
            'badge' => 'Customer stories',
            'title_prefix' => 'Loved by teams ',
            'title_accent' => 'everywhere',
            'subheading' => 'Real feedback from practices that switched last quarter.',
            'aside' => 'Average response time under 2 hours — we are here when you need us.',
            'items' => [
                ['name' => 'Sarah Lin', 'city' => 'Austin, TX', 'quote' => 'We cut admin time in half. Scheduling and billing finally feel connected.', 'gradient' => 'from-violet-500 to-purple-600'],
                ['name' => 'Marcus Reid', 'city' => 'London, UK', 'quote' => 'Patients get clearer reminders and we fill more slots. Simple win.', 'gradient' => 'from-indigo-500 to-blue-600'],
                ['name' => 'Elena Rossi', 'city' => 'Milan, IT', 'quote' => 'Reporting used to be a weekend project. Now it is one click.', 'gradient' => 'from-fuchsia-500 to-pink-600'],
                ['name' => 'James Porter', 'city' => 'Toronto, CA', 'quote' => 'Support actually understands clinics — not generic IT answers.', 'gradient' => 'from-amber-500 to-orange-600'],
                ['name' => 'Amira Hassan', 'city' => 'Dubai, AE', 'quote' => 'Arabic-friendly receipts and SMS templates were a big deal for us.', 'gradient' => 'from-emerald-500 to-teal-600'],
                ['name' => 'Olivia Chen', 'city' => 'Singapore', 'quote' => 'We onboarded three locations in a week without chaos.', 'gradient' => 'from-sky-500 to-indigo-600'],
            ],
        ];
    }
}
