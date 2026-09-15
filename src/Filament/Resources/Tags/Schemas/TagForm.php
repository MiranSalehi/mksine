<?php

namespace Miran\Mksine\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Filament\Forms\Components\CKEditor;
use Miran\Mksine\Filament\Forms\Components\SeoAnalysis;
use Miran\Mksine\Models\Tag;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema
            ->components([
                Section::make(__('mksine::tags.tag_information'))
                    ->key('tag_information')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('mksine::tags.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label(__('mksine::tags.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('mksine::tags.active'))
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('mksine::common.seo'))
                    ->key('seo')
                    ->schema([
                        TextInput::make('focus_keyphrase')
                            ->label(__('mksine::seo.focus_keyphrase'))
                            ->helperText(__('mksine::seo.focus_keyphrase_helper'))
                            ->maxLength(191)
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                        TextInput::make('meta_title')
                            ->label(__('mksine::tags.meta_title'))
                            ->maxLength(255)
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                        Textarea::make('meta_description')
                            ->label(__('mksine::tags.meta_description'))
                            ->rows(2)
                            ->maxLength(500)
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                        SeoAnalysis::make('seo_analysis')
                            ->titleField('name')
                            ->slugField('slug')
                            ->metaTitleField('meta_title')
                            ->metaDescriptionField('meta_description')
                            ->bodyField('description')
                            ->focusKeyphraseField('focus_keyphrase')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
                CKEditor::make('description')
                    ->label(__('mksine::tags.description'))
                    ->columnSpanFull(),
            ]);

        $formHookManager = app(FormHookManager::class);

        return $formHookManager->apply('tag.form', $schema);
    }

    /**
     * Multi-select used on Post and Page forms to attach active tags.
     */
    public static function assignmentSelect(string $labelKey): Select
    {
        return Select::make('tags')
            ->label(__($labelKey))
            ->multiple()
            ->relationship(
                'tags',
                'name',
                fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'),
            )
            ->searchable()
            ->preload()
            ->createOptionForm([
                TextInput::make('name')
                    ->label(__('mksine::tags.name'))
                    ->required()
                    ->maxLength(255),
            ])
            ->createOptionUsing(fn (array $data): int => Tag::findOrCreateFromName((string) $data['name'])->getKey())
            ->columnSpanFull();
    }
}
