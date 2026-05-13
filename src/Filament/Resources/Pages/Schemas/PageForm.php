<?php

namespace Miran\Mksine\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Filament\Forms\Components\CKEditor;
use Miran\Mksine\Filament\Forms\Components\PageBuilderField;
use Miran\Mksine\Models\Page;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        $formHookManager = app(FormHookManager::class);

        $schema = $schema
            ->components([
                Section::make(__('mksine::pages.page_information'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('mksine::pages.title'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label(__('mksine::pages.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                        Select::make('type')
                            ->label(__('mksine::pages.page_type'))
                            ->options(static function (?Page $record): array {
                                $pageBuilderEnabled = (bool) config('mksine.features.page_builder', false);
                                $options = [
                                    'simple' => __('mksine::pages.type_simple'),
                                ];
                                if ($pageBuilderEnabled) {
                                    $options['builder'] = __('mksine::pages.type_builder');
                                } elseif ($record instanceof Page && $record->getAttribute('type') === 'builder') {
                                    $options['builder'] = __('mksine::pages.type_builder');
                                }

                                return $options;
                            })
                            ->default('simple')
                            ->required()
                            ->native(false)
                            ->live(),
                        Select::make('status')
                            ->label(__('mksine::pages.status'))
                            ->options([
                                'draft' => __('mksine::pages.status_draft'),
                                'published' => __('mksine::pages.status_published'),
                                'scheduled' => __('mksine::pages.status_scheduled'),
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false)
                            ->live(),
                        DateTimePicker::make('published_at')
                            ->label(__('mksine::pages.publish_date'))
                            ->visible(fn ($get) => $get('status') === 'scheduled')
                            ->required(fn ($get) => $get('status') === 'scheduled')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('mksine::common.content'))
                    ->columnSpanFull()
                    ->schema([
                        CKEditor::make('content')
                            ->label(__('mksine::pages.content'))
                            ->live(false)
                            ->required(fn ($get) => $get('type') === 'simple')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('type') === 'simple'),
                Section::make(__('mksine::pages.page_builder'))
                    ->columnSpanFull()
                    ->schema([
                        PageBuilderField::make('builder_payload')
                            ->label(__('mksine::page_builder.field_label'))
                            ->columnSpanFull(),
                    ])
                    ->visible(static function ($get, ?Page $record): bool {
                        if ($get('type') !== 'builder') {
                            return false;
                        }

                        if (config('mksine.features.page_builder', false)) {
                            return true;
                        }

                        return $record instanceof Page && $record->getAttribute('type') === 'builder';
                    }),
                Section::make(__('mksine::common.seo'))
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('mksine::pages.meta_title'))
                            ->maxLength(60)
                            ->helperText(__('mksine::pages.meta_title_helper'))
                            ->columnSpanFull(),
                        Textarea::make('meta_description')
                            ->label(__('mksine::pages.meta_description'))
                            ->maxLength(160)
                            ->rows(3)
                            ->helperText(__('mksine::pages.meta_description_helper'))
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);

        // Apply form hooks
        return $formHookManager->apply('page.form', $schema);
    }
}
