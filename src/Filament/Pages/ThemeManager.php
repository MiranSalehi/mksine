<?php

namespace Miran\Mksine\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Core\Theme\ThemeManager as ThemeManagerService;
use ZipArchive;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ThemeManager extends Page
{
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected string $view = 'mksine::filament.pages.theme-manager';

    protected static ?string $slug = 'themes';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('mksine::themes.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('mksine::common.appearance');
    }

    public function getTitle(): string
    {
        return __('mksine::themes.title');
    }

    /**
     * Get all discovered themes.
     */
    public function getThemes(): Collection
    {
        return app(ThemeManagerService::class)->discover();
    }

    /**
     * Get the active theme identifier.
     */
    public function getActiveThemeIdentifier(): ?string
    {
        $activeTheme = app(ThemeManagerService::class)->getActive();

        return $activeTheme?->identifier;
    }

    public function getSubheading(): ?string
    {
        $themes = $this->getThemes();
        $total = $themes->count();
        $package = $themes->filter(fn ($t) => $t->isPackageTheme())->count();
        $project = $themes->filter(fn ($t) => $t->isProjectTheme())->count();

        return __('mksine::themes.themes_count', [
            'total' => $total,
            'package' => $package,
            'project' => $project,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('mksine::themes.upload_theme'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->schema([
                    FileUpload::make('theme_file')
                        ->label(__('mksine::themes.theme_zip_file'))
                        ->helperText(__('mksine::themes.theme_zip_helper'))
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(51200) // 50MB max
                        ->required()
                        ->storeFiles(false),
                ])
                ->action(function (array $data) {
                    $uploadedFile = $data['theme_file'];

                    if ($uploadedFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $tempPath = $uploadedFile->getRealPath();
                    } elseif ($uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                        $tempPath = $uploadedFile->getRealPath();
                    } elseif (is_string($uploadedFile)) {
                        $tempPath = storage_path('app/' . $uploadedFile);
                    } else {
                        Notification::make()
                            ->title(__('mksine::themes.upload_failed'))
                            ->body(__('mksine::plugins.invalid_file_format'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->processThemeUpload($tempPath);
                }),

            Action::make('discover')
                ->label(__('mksine::themes.discover_themes'))
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->action(function () {
                    app(ThemeManagerService::class)->clearCache();

                    Notification::make()
                        ->title(__('mksine::themes.themes_discovered'))
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    /**
     * Process theme ZIP upload.
     */
    protected function processThemeUpload(string $tempPath): void
    {
        $themesPath = resource_path('views/themes');
        $tempDir = storage_path('app/theme-temp');

        // Ensure directories exist
        if (! File::isDirectory($themesPath)) {
            File::makeDirectory($themesPath, 0755, true);
        }
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        try {
            if (! File::exists($tempPath)) {
                throw new \RuntimeException(__('mksine::themes.uploaded_file_not_found'));
            }

            $zip = new ZipArchive;
            $openResult = $zip->open($tempPath);

            if ($openResult !== true) {
                throw new \RuntimeException(__('mksine::themes.zip_open_failed', ['code' => $openResult]));
            }

            // Find the root folder in ZIP (the theme folder)
            $rootFolder = null;
            $hasThemeJson = false;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                // Check for theme.json in root or first-level folder
                if ($name === 'theme.json') {
                    $rootFolder = '';
                    $hasThemeJson = true;

                    break;
                }

                if (preg_match('#^([^/]+)/theme\.json$#', $name, $matches)) {
                    $rootFolder = $matches[1];
                    $hasThemeJson = true;

                    break;
                }
            }

            if (! $hasThemeJson) {
                $zip->close();

                throw new \RuntimeException(__('mksine::themes.invalid_theme_no_json'));
            }

            // Get theme.json content
            $themeJsonContent = $rootFolder === ''
                ? $zip->getFromName('theme.json')
                : $zip->getFromName($rootFolder . '/theme.json');

            $themeJson = json_decode($themeJsonContent, true);

            if (! is_array($themeJson) || empty($themeJson['name'])) {
                $zip->close();

                throw new \RuntimeException(__('mksine::themes.invalid_theme_missing_name'));
            }

            // Determine theme identifier
            $themeIdentifier = $rootFolder ?: strtolower(str_replace(' ', '-', $themeJson['name']));
            $targetPath = $themesPath . '/' . $themeIdentifier;

            // Check if theme already exists
            if (File::isDirectory($targetPath)) {
                $zip->close();

                throw new \RuntimeException(__('mksine::themes.theme_already_exists', ['id' => $themeIdentifier]));
            }

            // Extract to themes directory
            if ($rootFolder === '') {
                File::makeDirectory($targetPath, 0755, true);
                $zip->extractTo($targetPath);
                $zip->close();
            } else {
                $tempExtractPath = $tempDir . '/extract-' . uniqid();
                File::makeDirectory($tempExtractPath, 0755, true);
                $zip->extractTo($tempExtractPath);
                $zip->close();

                File::moveDirectory($tempExtractPath . '/' . $rootFolder, $targetPath);
                File::deleteDirectory($tempExtractPath);
            }

            // Clear theme cache
            app(ThemeManagerService::class)->clearCache();

            // Publish assets if available
            $themeManager = app(ThemeManagerService::class);
            $themeManager->publishAssets($themeIdentifier);

            Notification::make()
                ->title(__('mksine::themes.theme_uploaded'))
                ->body(__('mksine::themes.theme_uploaded_name', [
                    'name' => $themeJson['name'] ?? $themeIdentifier,
                    'version' => $themeJson['version'] ?? '1.0.0',
                ]))
                ->success()
                ->send();

            $this->redirect(static::getUrl());

        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('mksine::themes.upload_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Activate a theme.
     */
    public function activateTheme(string $identifier): void
    {
        $themeManager = app(ThemeManagerService::class);
        $theme = $themeManager->get($identifier);

        if (! $theme) {
            Notification::make()
                ->title(__('mksine::themes.theme_not_found'))
                ->danger()
                ->send();

            return;
        }

        $activated = $themeManager->activate($identifier);

        if ($activated) {
            if ($theme->isProjectTheme()) {
                $themeManager->publishAssets($identifier);
            }

            Notification::make()
                ->title(__('mksine::themes.theme_activated'))
                ->body(__('mksine::themes.theme_activated_name', ['name' => $theme->name]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('mksine::themes.failed_activate_theme'))
                ->danger()
                ->send();
        }
    }

    /**
     * Delete a theme (project themes only).
     */
    public function deleteTheme(string $identifier): void
    {
        $themeManager = app(ThemeManagerService::class);
        $theme = $themeManager->get($identifier);

        if (! $theme) {
            Notification::make()
                ->title(__('mksine::themes.theme_not_found'))
                ->danger()
                ->send();

            return;
        }

        // Only allow deletion of project themes
        if ($theme->isPackageTheme()) {
            Notification::make()
                ->title(__('mksine::themes.cannot_delete_package_theme'))
                ->body(__('mksine::themes.package_theme_explanation'))
                ->danger()
                ->send();

            return;
        }

        // Cannot delete active theme
        if ($identifier === $this->getActiveThemeIdentifier()) {
            Notification::make()
                ->title(__('mksine::themes.cannot_delete_active_theme'))
                ->body(__('mksine::themes.activate_different_first'))
                ->danger()
                ->send();

            return;
        }

        try {
            // Safety check: ensure path is within themes directory
            $themesDir = resource_path('views/themes');
            $realThemePath = realpath($theme->path);
            $realThemesDir = realpath($themesDir);

            if (! $realThemePath || ! $realThemesDir || ! str_starts_with($realThemePath, $realThemesDir)) {
                throw new \RuntimeException(__('mksine::themes.cannot_delete_outside_themes'));
            }

            // Delete theme directory
            File::deleteDirectory($theme->path);

            // Delete published assets
            $publicAssetsPath = public_path("themes/{$identifier}");
            if (File::isDirectory($publicAssetsPath)) {
                File::deleteDirectory($publicAssetsPath);
            }

            // Clear cache
            $themeManager->clearCache();

            Notification::make()
                ->title(__('mksine::themes.theme_deleted'))
                ->body(__('mksine::themes.theme_deleted_name', ['name' => $theme->name]))
                ->success()
                ->send();

            $this->redirect(static::getUrl());

        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('mksine::themes.delete_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Actions (for modals opened from content, e.g. Custom CSS/JS per theme).
     */
    protected function getActions(): array
    {
        return [
            $this->customCssJsAction(),
        ];
    }

    /**
     * Custom CSS/JS editor action – opens Filament modal with form.
     */
    public function customCssJsAction(): Action
    {
        return Action::make('customCssJs')
            ->label(__('mksine::themes.custom_css_js'))
            ->icon('heroicon-o-code-bracket-square')
            ->modalHeading(fn (array $arguments) => __('mksine::themes.custom_css_js') . ' – ' . (app(ThemeManagerService::class)->get($arguments['themeIdentifier'] ?? '')?->name ?? ($arguments['themeIdentifier'] ?? '')))
            ->modalDescription(__('mksine::themes.custom_css_js_modal_description'))
            ->modalWidth('4xl')
            ->fillForm(function (array $arguments): array {
                $id = $arguments['themeIdentifier'] ?? '';
                $manager = app(ThemeManagerService::class);
                $extra = $manager->getExtraAssets($id);

                return [
                    'custom_css' => $manager->getCustomContent($id, 'css'),
                    'custom_js' => $manager->getCustomContent($id, 'js'),
                    'extra_css_files' => implode("\n", $extra['css']),
                    'extra_js_files' => implode("\n", $extra['js']),
                ];
            })
            ->schema([
                CodeEditor::make('custom_css')
                    ->language(CodeEditor\Enums\Language::Css)
                    ->label(__('mksine::themes.custom_css'))
                    ->columnSpanFull(),
                CodeEditor::make('custom_js')
                    ->language(CodeEditor\Enums\Language::JavaScript)
                    ->label(__('mksine::themes.custom_js'))
                    ->columnSpanFull(),
                Textarea::make('extra_css_files')
                    ->label(__('mksine::themes.additional_css_files'))
                    ->placeholder(__('mksine::themes.additional_css_placeholder'))
                    ->helperText(__('mksine::themes.additional_css_helper'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('extra_js_files')
                    ->label(__('mksine::themes.additional_js_files'))
                    ->placeholder(__('mksine::themes.additional_js_placeholder'))
                    ->helperText(__('mksine::themes.additional_js_helper'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, array $arguments): void {
                $id = $arguments['themeIdentifier'] ?? '';
                if ($id === '') {
                    return;
                }
                $manager = app(ThemeManagerService::class);
                $manager->putCustomContent($id, 'css', $data['custom_css'] ?? '');
                $manager->putCustomContent($id, 'js', $data['custom_js'] ?? '');

                $extraCss = array_filter(array_map('trim', explode("\n", $data['extra_css_files'] ?? '')));
                $extraJs = array_filter(array_map('trim', explode("\n", $data['extra_js_files'] ?? '')));
                $manager->setExtraAssets($id, array_values($extraCss), array_values($extraJs));

                Notification::make()
                    ->title(__('mksine::themes.custom_css_js_saved'))
                    ->body(__('mksine::themes.custom_css_js_changes_notice'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Refresh theme list (clear cache).
     */
    public function refreshThemes(): void
    {
        app(ThemeManagerService::class)->clearCache();

        Notification::make()
            ->title(__('mksine::themes.theme_list_refreshed'))
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }
}
