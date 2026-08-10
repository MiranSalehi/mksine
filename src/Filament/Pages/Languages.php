<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Support\Icons\Heroicon;
use Miran\Mksine\Core\Translation\AdminTranslationManager;
use Miran\Mksine\Core\Translation\TranslationFileManager;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;

class Languages extends Page implements HasSchemas
{
    use InteractsWithSchemas, HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected static ?string $slug = 'languages';

    protected static ?int $navigationSort = 10;

    protected string $view = 'mksine::filament.pages.languages';

    /** @var array{locale: ?string, source?: string, file: ?string, translations: array<string, string>} */
    public array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('mksine::languages.navigation_label');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminSidebarNavigation::toolsGroup();
    }

    public function getTitle(): string
    {
        return __('mksine::languages.title');
    }

    public function mount(): void
    {
        $manager = app(TranslationFileManager::class);
        $admin = app(AdminTranslationManager::class);
        $locales = $manager->getAvailableLocales();
        $this->data['translations'] = $this->data['translations'] ?? [];
        $this->data['source'] = $this->data['source'] ?? 'app';
        if (! empty($this->data['locale']) && ! $admin->isAllowedSource($this->data['source'])) {
            $this->data['source'] = 'app';
        }
        if ($locales !== [] && empty($this->data['locale'])) {
            $locale = $locales[0];
            $this->data['locale'] = $locale;
            $source = $this->data['source'] ?? 'app';
            if (! $admin->isAllowedSource($source)) {
                $source = 'app';
                $this->data['source'] = $source;
            }
            $files = $admin->getFilesForLocaleAndSource($locale, $source);
            $first = $files !== [] ? array_values($files)[0] : null;
            if ($first !== null) {
                $this->data['file'] = $first;
                $this->data['translations'] = $admin->getTranslations($locale, $source, $first);
            }
        }
        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        $manager = app(TranslationFileManager::class);
        $admin = app(AdminTranslationManager::class);

        return $form
            ->statePath('data')
            ->schema([
                Select::make('locale')
                    ->label(__('mksine::languages.language'))
                    ->options(fn () => array_combine(
                        $manager->getAvailableLocales(),
                        $manager->getAvailableLocales()
                    ))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, $set) use ($admin): void {
                        $set('source', 'app');
                        $set('file', null);
                        $set('translations', []);
                        if ($state) {
                            $files = $admin->getFilesForLocaleAndSource($state, 'app');
                            $firstValue = $files !== [] ? array_values($files)[0] : null;
                            if ($firstValue !== null) {
                                $set('file', $firstValue);
                                $set('translations', $admin->getTranslations($state, 'app', $firstValue));
                            }
                        }
                    }),
                Select::make('source')
                    ->label(__('mksine::languages.translation_source'))
                    ->options(fn () => $admin->getSourceOptions($this->data['locale'] ?? null))
                    ->required()
                    ->live()
                    ->helperText(function () {
                        $source = $this->data['source'] ?? 'app';
                        if ($source === 'app') {
                            return null;
                        }

                        return __('mksine::languages.save_republishes_vendor_hint');
                    })
                    ->afterStateUpdated(function (?string $state, $get, $set) use ($admin): void {
                        $locale = $get('locale');
                        $set('file', null);
                        $set('translations', []);
                        if (empty($locale) || empty($state) || ! $admin->isAllowedSource($state)) {
                            return;
                        }
                        $files = $admin->getFilesForLocaleAndSource($locale, $state);
                        $firstValue = $files !== [] ? array_values($files)[0] : null;
                        if ($firstValue !== null) {
                            $set('file', $firstValue);
                            $set('translations', $admin->getTranslations($locale, $state, $firstValue));
                        }
                    }),
                Select::make('file')
                    ->label(__('mksine::languages.translation_file'))
                    ->options(function () use ($admin) {
                        $locale = $this->data['locale'] ?? null;
                        $source = $this->data['source'] ?? 'app';
                        if (empty($locale)) {
                            return [];
                        }
                        $files = $admin->getFilesForLocaleAndSource($locale, $source);

                        return array_flip($files);
                    })
                    ->helperText(function () use ($admin) {
                        $locale = $this->data['locale'] ?? null;
                        $source = $this->data['source'] ?? 'app';
                        if (empty($locale)) {
                            return null;
                        }

                        return __('mksine::languages.files_from_directory', [
                            'path' => $admin->getFilesDirectoryHint($locale, $source),
                        ]);
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, $get, $set) use ($admin): void {
                        $locale = $get('locale');
                        $source = $get('source') ?? 'app';
                        if (! empty($locale) && $state) {
                            $set('translations', $admin->getTranslations($locale, $source, $state));
                        }
                    }),
                KeyValue::make('translations')
                    ->label(__('mksine::languages.strings'))
                    ->keyLabel(__('mksine::languages.key'))
                    ->valueLabel(__('mksine::languages.translation'))
                    ->addActionLabel(__('mksine::languages.add_string'))
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->visible(fn () => ! empty($this->data['locale'])
                        && ! empty($this->data['source'])
                        && ! empty($this->data['file'])),
            ]);
    }

    protected function loadTranslations(): void
    {
        if (empty($this->data['locale']) || empty($this->data['file'])) {
            $this->data['translations'] = [];

            return;
        }
        $admin = app(AdminTranslationManager::class);
        $source = $this->data['source'] ?? 'app';
        $this->data['translations'] = $admin->getTranslations(
            $this->data['locale'],
            $source,
            $this->data['file']
        );
    }

    public function saveTranslations(): void
    {
        $admin = app(AdminTranslationManager::class);
        if (empty($this->data['locale']) || empty($this->data['file'])) {
            Notification::make()
                ->title(__('mksine::languages.please_select_language_and_file'))
                ->warning()
                ->send();

            return;
        }

        $source = $this->data['source'] ?? 'app';
        if (! $admin->isAllowedSource($source)) {
            Notification::make()
                ->title(__('mksine::languages.save_failed'))
                ->body(__('mksine::languages.invalid_translation_source'))
                ->danger()
                ->send();

            return;
        }

        try {
            $admin->setTranslations(
                $this->data['locale'],
                $source,
                $this->data['file'],
                $this->data['translations'] ?? []
            );
            Notification::make()
                ->title(__('mksine::languages.translations_saved'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('mksine::languages.save_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function addLanguage(string $code, ?string $copyFrom = null): void
    {
        $manager = app(TranslationFileManager::class);
        if (! $manager->isValidLocaleCode($code)) {
            Notification::make()
                ->title(__('mksine::languages.invalid_locale_code'))
                ->body(__('mksine::languages.locale_code_help'))
                ->danger()
                ->send();

            return;
        }

        try {
            $manager->addLocale($code, $copyFrom);
            Notification::make()
                ->title(__('mksine::languages.language_added'))
                ->body(__('mksine::languages.locale_added', ['code' => $code]))
                ->success()
                ->send();
            $this->redirect(static::getUrl());
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('mksine::languages.failed_to_add_language'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $manager = app(TranslationFileManager::class);
        $locales = $manager->getAvailableLocales();

        return [
            Action::make('addLanguage')
                ->label(__('mksine::languages.add_language'))
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    TextInput::make('code')
                        ->label(__('mksine::languages.locale_code'))
                        ->placeholder(__('mksine::languages.locale_code_placeholder'))
                        ->required()
                        ->maxLength(12)
                        ->helperText(__('mksine::languages.locale_code_help')),
                    Select::make('copyFrom')
                        ->label(__('mksine::languages.copy_translations_from'))
                        ->placeholder(__('mksine::languages.empty_start_from_scratch'))
                        ->options(array_combine($locales, $locales)),
                ])
                ->action(function (array $data): void {
                    $this->addLanguage($data['code'], $data['copyFrom'] ?? null);
                }),
            Action::make('save')
                ->label(__('mksine::languages.save_translations'))
                ->icon('heroicon-o-check')
                ->action('saveTranslations'),
        ];
    }

    public function getSubheading(): ?string
    {
        $manager = app(TranslationFileManager::class);
        $locales = $manager->getAvailableLocales();

        return count($locales) > 0
            ? __('mksine::languages.edit_translations_path', ['path' => $manager->getLangPath()])
            : __('mksine::languages.no_languages_yet');
    }
}
