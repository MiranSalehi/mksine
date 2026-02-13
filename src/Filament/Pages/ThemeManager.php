<?php

namespace Miran\Mksine\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Core\Theme\ThemeManager as ThemeManagerService;
use ZipArchive;

class ThemeManager extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected string $view = 'mksine::filament.pages.theme-manager';

    protected static ?string $navigationLabel = 'Themes';

    protected static ?string $title = 'Theme Manager';

    protected static ?string $slug = 'themes';

    protected static ?int $navigationSort = 8;

    protected static string|\UnitEnum|null $navigationGroup = 'Appearance';

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

        return __(':total themes (:package package, :project project)', [
            'total' => $total,
            'package' => $package,
            'project' => $project,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('Upload Theme'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('theme_file')
                        ->label(__('Theme ZIP File'))
                        ->helperText(__('Upload a theme as a .zip file. The ZIP should contain a folder with theme.json inside.'))
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
                            ->title(__('Upload failed'))
                            ->body(__('Invalid file format.'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->processThemeUpload($tempPath);
                }),

            Action::make('discover')
                ->label(__('Discover Themes'))
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->action(function () {
                    app(ThemeManagerService::class)->clearCache();

                    Notification::make()
                        ->title(__('Themes discovered successfully'))
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
                throw new \RuntimeException(__('Uploaded file not found.'));
            }

            $zip = new ZipArchive;
            $openResult = $zip->open($tempPath);

            if ($openResult !== true) {
                throw new \RuntimeException(__('Failed to open ZIP file. Error code: :code', ['code' => $openResult]));
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

                throw new \RuntimeException(__('Invalid theme: theme.json not found in the ZIP file.'));
            }

            // Get theme.json content
            $themeJsonContent = $rootFolder === ''
                ? $zip->getFromName('theme.json')
                : $zip->getFromName($rootFolder . '/theme.json');

            $themeJson = json_decode($themeJsonContent, true);

            if (! is_array($themeJson) || empty($themeJson['name'])) {
                $zip->close();

                throw new \RuntimeException(__('Invalid theme.json: missing theme name.'));
            }

            // Determine theme identifier
            $themeIdentifier = $rootFolder ?: strtolower(str_replace(' ', '-', $themeJson['name']));
            $targetPath = $themesPath . '/' . $themeIdentifier;

            // Check if theme already exists
            if (File::isDirectory($targetPath)) {
                $zip->close();

                throw new \RuntimeException(__('Theme ":id" already exists. Please delete it first.', ['id' => $themeIdentifier]));
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
                ->title(__('Theme uploaded successfully'))
                ->body(__('Theme ":name" (v:version) has been uploaded.', [
                    'name' => $themeJson['name'] ?? $themeIdentifier,
                    'version' => $themeJson['version'] ?? '1.0.0',
                ]))
                ->success()
                ->send();

            $this->redirect(static::getUrl());

        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Upload failed'))
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
                ->title(__('Theme not found'))
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
                ->title(__('Theme activated'))
                ->body(__('The theme ":name" has been activated.', ['name' => $theme->name]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('Failed to activate theme'))
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
                ->title(__('Theme not found'))
                ->danger()
                ->send();

            return;
        }

        // Only allow deletion of project themes
        if ($theme->isPackageTheme()) {
            Notification::make()
                ->title(__('Cannot delete package theme'))
                ->body(__('Package themes cannot be deleted. They are part of the core system.'))
                ->danger()
                ->send();

            return;
        }

        // Cannot delete active theme
        if ($identifier === $this->getActiveThemeIdentifier()) {
            Notification::make()
                ->title(__('Cannot delete active theme'))
                ->body(__('Please activate a different theme before deleting this one.'))
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
                throw new \RuntimeException(__('Cannot delete theme outside of themes directory.'));
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
                ->title(__('Theme deleted'))
                ->body(__('The theme ":name" has been deleted.', ['name' => $theme->name]))
                ->success()
                ->send();

            $this->redirect(static::getUrl());

        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Delete failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Refresh theme list (clear cache).
     */
    public function refreshThemes(): void
    {
        app(ThemeManagerService::class)->clearCache();

        Notification::make()
            ->title(__('Theme list refreshed'))
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }
}
