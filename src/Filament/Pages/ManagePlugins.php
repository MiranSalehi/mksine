<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Core\Plugins\PluginManager;
use ZipArchive;

class ManagePlugins extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected string $view = 'mksine::filament.pages.manage-plugins';

    protected static ?int $navigationSort = 100;

    public array $plugins = [];

    public function mount(): void
    {
        $this->loadPlugins(true);
    }

    public function loadPlugins(bool $rediscover = false): void
    {
        if ($rediscover) {
            // Forget the singleton instance to force fresh discovery
            app()->forgetInstance(PluginManager::class);
        }

        $pluginManager = app(PluginManager::class);
        $this->plugins = $pluginManager->getAllPlugins();
    }

    /**
     * Force reload the page to show fresh data.
     */
    protected function refreshPage(): void
    {
        $this->redirect(static::getUrl());
    }

    public static function getNavigationLabel(): string
    {
        return __('Plugins');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System');
    }

    public function getTitle(): string
    {
        return __('Manage Plugins');
    }

    public function getSubheading(): ?string
    {
        $active = collect($this->plugins)->where('status', 'active')->count();
        $total = count($this->plugins);

        return __(':active of :total plugins active', ['active' => $active, 'total' => $total]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('Upload Plugin'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('plugin_file')
                        ->label(__('Plugin ZIP File'))
                        ->helperText(__('Upload a plugin as a .zip file. The ZIP should contain a folder with plugin.php inside.'))
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(51200) // 50MB max
                        ->required()
                        ->storeFiles(false), // Don't store, we handle it manually
                ])
                ->action(function (array $data) {
                    $uploadedFile = $data['plugin_file'];

                    // Handle Livewire uploaded file
                    if ($uploadedFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $tempPath = $uploadedFile->getRealPath();
                    } elseif ($uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                        $tempPath = $uploadedFile->getRealPath();
                    } elseif (is_string($uploadedFile)) {
                        // It's a stored path
                        $tempPath = storage_path('app/' . $uploadedFile);
                    } else {
                        Notification::make()
                            ->title(__('Upload failed'))
                            ->body(__('Invalid file format.'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->processPluginUpload($tempPath);
                }),

            Action::make('discover')
                ->label(__('Discover Plugins'))
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->action(function () {
                    Notification::make()
                        ->title(__('Plugins discovered successfully'))
                        ->success()
                        ->send();

                    $this->refreshPage();
                }),
        ];
    }

    protected function processPluginUpload(string $tempPath): void
    {
        $pluginsPath = base_path('plugins');
        $tempDir = storage_path('app/plugin-temp');

        // Ensure directories exist
        if (! File::isDirectory($pluginsPath)) {
            File::makeDirectory($pluginsPath, 0755, true);
        }
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        try {
            // Validate file exists
            if (! File::exists($tempPath)) {
                throw new \RuntimeException(__('Uploaded file not found.'));
            }

            $zip = new ZipArchive;
            $openResult = $zip->open($tempPath);

            if ($openResult !== true) {
                throw new \RuntimeException(__('Failed to open ZIP file. Error code: :code', ['code' => $openResult]));
            }

            // Find the root folder in ZIP (the plugin folder)
            $rootFolder = null;
            $hasPluginPhp = false;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                // Check for plugin.php in root or first-level folder
                if ($name === 'plugin.php') {
                    $rootFolder = '';
                    $hasPluginPhp = true;

                    break;
                }

                if (preg_match('#^([^/]+)/plugin\.php$#', $name, $matches)) {
                    $rootFolder = $matches[1];
                    $hasPluginPhp = true;

                    break;
                }
            }

            if (! $hasPluginPhp) {
                $zip->close();

                throw new \RuntimeException(__('Invalid plugin: plugin.php not found in the ZIP file.'));
            }

            // Determine plugin ID from manifest
            $manifestContent = $rootFolder === ''
                ? $zip->getFromName('plugin.php')
                : $zip->getFromName($rootFolder . '/plugin.php');

            // Parse plugin.php to get ID
            $tempManifestPath = $tempDir . '/temp-manifest-' . uniqid() . '.php';
            File::put($tempManifestPath, $manifestContent);
            $manifest = require $tempManifestPath;
            File::delete($tempManifestPath);

            if (! is_array($manifest) || empty($manifest['id'])) {
                $zip->close();

                throw new \RuntimeException(__('Invalid plugin manifest: missing plugin ID.'));
            }

            $pluginId = $manifest['id'];
            $targetPath = $pluginsPath . '/' . $pluginId;

            // Check if plugin already exists
            if (File::isDirectory($targetPath)) {
                $zip->close();

                throw new \RuntimeException(__('Plugin ":id" already exists. Please uninstall it first.', ['id' => $pluginId]));
            }

            // Extract to plugins directory
            if ($rootFolder === '') {
                // Plugin files are at root of ZIP
                File::makeDirectory($targetPath, 0755, true);
                $zip->extractTo($targetPath);
                $zip->close();
            } else {
                // Plugin files are in a subfolder
                $tempExtractPath = $tempDir . '/extract-' . uniqid();
                File::makeDirectory($tempExtractPath, 0755, true);
                $zip->extractTo($tempExtractPath);
                $zip->close();

                // Move the plugin folder to plugins directory
                File::moveDirectory($tempExtractPath . '/' . $rootFolder, $targetPath);
                File::deleteDirectory($tempExtractPath);
            }

            Notification::make()
                ->title(__('Plugin uploaded successfully'))
                ->body(__('Plugin ":name" (v:version) has been uploaded. You can now install and activate it.', [
                    'name' => $manifest['name'] ?? $pluginId,
                    'version' => $manifest['version'] ?? '?',
                ]))
                ->success()
                ->send();

            $this->refreshPage();

        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Upload failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function installPlugin(string $pluginId): void
    {
        try {
            $pluginManager = app(PluginManager::class);
            $pluginManager->install($pluginId);

            Notification::make()
                ->title(__('Plugin installed successfully'))
                ->success()
                ->send();

            $this->refreshPage();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Installation failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function activatePlugin(string $pluginId): void
    {
        try {
            $pluginManager = app(PluginManager::class);
            $pluginManager->activate($pluginId);

            Notification::make()
                ->title(__('Plugin activated successfully'))
                ->success()
                ->send();

            $this->refreshPage();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Activation failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deactivatePlugin(string $pluginId): void
    {
        try {
            $pluginManager = app(PluginManager::class);
            $pluginManager->deactivate($pluginId);

            Notification::make()
                ->title(__('Plugin deactivated successfully'))
                ->success()
                ->send();

            $this->refreshPage();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Deactivation failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function uninstallPlugin(string $pluginId): void
    {
        try {
            $pluginManager = app(PluginManager::class);
            $pluginManager->uninstall($pluginId, false);

            Notification::make()
                ->title(__('Plugin uninstalled successfully'))
                ->success()
                ->send();

            $this->refreshPage();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Uninstallation failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deletePlugin(string $pluginId): void
    {
        try {
            $pluginManager = app(PluginManager::class);

            // Get manifest directly (more reliable than searching summary)
            $manifest = $pluginManager->getManifest($pluginId);

            if (! $manifest) {
                throw new \RuntimeException(__('Plugin not found.'));
            }

            // Check status
            $status = $pluginManager->getStatus($pluginId);

            // Only allow deletion if plugin is not installed
            if ($status !== 'not_installed') {
                throw new \RuntimeException(__('Please uninstall the plugin before deleting it.'));
            }

            // Get plugin path from manifest
            $pluginPath = $manifest->basePath();

            if (empty($pluginPath)) {
                throw new \RuntimeException(__('Cannot delete this plugin. It may be a Composer package.'));
            }

            // Safety check: ensure path is within plugins directory
            $pluginsDir = base_path('plugins');
            $realPluginPath = realpath($pluginPath);
            $realPluginsDir = realpath($pluginsDir);

            if (! $realPluginPath || ! $realPluginsDir || ! str_starts_with($realPluginPath, $realPluginsDir)) {
                throw new \RuntimeException(__('Cannot delete plugin outside of plugins directory.'));
            }

            // Delete the plugin directory
            File::deleteDirectory($pluginPath);

            Notification::make()
                ->title(__('Plugin deleted successfully'))
                ->body(__('All plugin files have been removed from the server.'))
                ->success()
                ->send();

            $this->refreshPage();

        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Delete failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'inactive' => 'warning',
            'installed' => 'info',
            'not_installed' => 'gray',
            'boot_failed' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => __('Active'),
            'inactive' => __('Inactive'),
            'installed' => __('Installed'),
            'not_installed' => __('Not Installed'),
            'boot_failed' => __('Boot Failed'),
            default => $status,
        };
    }
}
