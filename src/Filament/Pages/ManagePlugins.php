<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Core\Plugins\PluginLogger;
use Miran\Mksine\Core\Plugins\PluginManager;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use ZipArchive;

class ManagePlugins extends Page
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected string $view = 'mksine::filament.pages.manage-plugins';

    protected static ?int $navigationSort = 100;

    public array $plugins = [];

    public ?string $pluginLogContent = null;

    public string $pluginLogPluginName = '';

    public string $pluginLogPluginId = '';

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
        return __('mksine::plugins.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('mksine::common.system');
    }

    public function getTitle(): string
    {
        return __('mksine::plugins.title');
    }

    public function getSubheading(): ?string
    {
        $active = collect($this->plugins)->where('status', 'active')->count();
        $total = count($this->plugins);

        return __('mksine::plugins.subheading', ['active' => $active, 'total' => $total]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('mksine::plugins.upload_plugin'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('plugin_file')
                        ->label(__('mksine::plugins.plugin_zip_file'))
                        ->helperText(__('mksine::plugins.plugin_zip_helper'))
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
                            ->title(__('mksine::plugins.upload_failed'))
                            ->body(__('mksine::plugins.invalid_file_format'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->processPluginUpload($tempPath);
                }),

            Action::make('discover')
                ->label(__('mksine::plugins.discover_plugins'))
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->action(function () {
                    Notification::make()
                        ->title(__('mksine::plugins.plugins_discovered'))
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
                throw new \RuntimeException(__('mksine::plugins.uploaded_file_not_found'));
            }

            $zip = new ZipArchive;
            $openResult = $zip->open($tempPath);

            if ($openResult !== true) {
                throw new \RuntimeException(__('mksine::plugins.zip_open_failed', ['code' => $openResult]));
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

                throw new \RuntimeException(__('mksine::plugins.invalid_plugin_no_manifest'));
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

                throw new \RuntimeException(__('mksine::plugins.invalid_plugin_missing_id'));
            }

            $pluginId = $manifest['id'];
            $targetPath = $pluginsPath . '/' . $pluginId;

            // Check if plugin already exists
            if (File::isDirectory($targetPath)) {
                $zip->close();

                throw new \RuntimeException(__('mksine::plugins.plugin_already_exists', ['id' => $pluginId]));
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
                ->title(__('mksine::plugins.plugin_uploaded'))
                ->body(__('mksine::plugins.plugin_uploaded_body', [
                    'name' => $manifest['name'] ?? $pluginId,
                    'version' => $manifest['version'] ?? '?',
                ]))
                ->success()
                ->send();

            $this->refreshPage();

        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('mksine::plugins.upload_failed'))
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
                ->title(__('mksine::plugins.plugin_installed'))
                ->success()
                ->send();

            $this->refreshPage();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('mksine::plugins.installation_failed'))
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

            // Publish assets to public/plugins/{id}/ after activation (mirrors theme behaviour).
            $manifest = $pluginManager->getManifest($pluginId);
            if ($manifest) {
                $manifest->publishAssets();
            }

            Notification::make()
                ->title(__('mksine::plugins.plugin_activated'))
                ->success()
                ->send();

            $this->refreshPage();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('mksine::plugins.activation_failed'))
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
                ->title(__('mksine::plugins.plugin_deactivated'))
                ->success()
                ->send();

            $this->refreshPage();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('mksine::plugins.deactivation_failed'))
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
                ->title(__('mksine::plugins.plugin_uninstalled'))
                ->success()
                ->send();

            $this->refreshPage();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('mksine::plugins.uninstallation_failed'))
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
                throw new \RuntimeException(__('mksine::plugins.plugin_not_found'));
            }

            // Check status
            $status = $pluginManager->getStatus($pluginId);

            // Only allow deletion if plugin is not installed
            if ($status !== 'not_installed') {
                throw new \RuntimeException(__('mksine::plugins.please_uninstall_before_delete'));
            }

            // Get plugin path from manifest
            $pluginPath = $manifest->basePath();

            if (empty($pluginPath)) {
                throw new \RuntimeException(__('mksine::plugins.cannot_delete_composer_plugin'));
            }

            // Safety check: ensure path is within plugins directory
            $pluginsDir = base_path('plugins');
            $realPluginPath = realpath($pluginPath);
            $realPluginsDir = realpath($pluginsDir);

            if (! $realPluginPath || ! $realPluginsDir || ! str_starts_with($realPluginPath, $realPluginsDir)) {
                throw new \RuntimeException(__('mksine::plugins.cannot_delete_outside_plugins_dir'));
            }

            // Delete the plugin directory
            File::deleteDirectory($pluginPath);

            Notification::make()
                ->title(__('mksine::plugins.plugin_deleted'))
                ->body(__('mksine::plugins.all_files_removed'))
                ->success()
                ->send();

            $this->refreshPage();

        } catch (\Exception $e) {
            Notification::make()
                ->title(__('mksine::plugins.delete_failed'))
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
            'active' => __('mksine::plugins.status_active'),
            'inactive' => __('mksine::plugins.status_inactive'),
            'installed' => __('mksine::plugins.status_installed'),
            'not_installed' => __('mksine::plugins.status_not_installed'),
            'boot_failed' => __('mksine::plugins.status_boot_failed'),
            default => $status,
        };
    }

    public function getPluginLogContent(string $pluginId): ?string
    {
        return app(PluginLogger::class)->getLogContent($pluginId);
    }

    public function hasPluginLog(string $pluginId): bool
    {
        return app(PluginLogger::class)->hasLog($pluginId);
    }

    public function openPluginLog(string $pluginId): void
    {
        $logger = app(PluginLogger::class);
        $this->pluginLogPluginId = $pluginId;
        $this->pluginLogContent = $logger->getLogContent($pluginId) ?? __('mksine::plugins.no_log_entries_yet');
        $plugin = collect($this->plugins)->firstWhere('id', $pluginId);
        $this->pluginLogPluginName = $plugin['name'] ?? $pluginId;

        $this->dispatch('open-modal', id: 'plugin-log-modal');
    }

    public function clearPluginLog(): void
    {
        if ($this->pluginLogPluginId === '') {
            return;
        }

        $logger = app(PluginLogger::class);
        $logger->clearLog($this->pluginLogPluginId);

        $this->pluginLogContent = __('mksine::plugins.no_log_entries_yet');
    }

    public function closePluginLogModal(): void
    {
        $this->dispatch('close-modal', id: 'plugin-log-modal');
    }
}
