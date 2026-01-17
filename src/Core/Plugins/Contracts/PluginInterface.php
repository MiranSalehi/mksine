<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins\Contracts;

/**
 * Interface that all MKS CMS plugins must implement.
 *
 * This interface defines the contract for plugin lifecycle management.
 * Each plugin must provide implementations for install, activate,
 * deactivate, and uninstall operations.
 */
interface PluginInterface
{
    /**
     * Get the unique plugin identifier.
     * This must match the 'id' in plugin.php manifest.
     *
     * @return string Plugin ID (e.g., 'mks-shop')
     */
    public function id(): string;

    /**
     * Called when plugin is first installed.
     * Use this for:
     * - Running migrations
     * - Creating initial data
     * - Setting up directories
     */
    public function install(): void;

    /**
     * Called when plugin is activated.
     * Use this for:
     * - Registering hooks/listeners
     * - Enabling features
     */
    public function activate(): void;

    /**
     * Called when plugin is deactivated.
     * Use this for:
     * - Unregistering hooks/listeners
     * - Disabling features
     *
     * Note: Data should NOT be deleted here.
     */
    public function deactivate(): void;

    /**
     * Called when plugin is uninstalled.
     *
     * @param  bool  $deleteData  If true, delete all plugin data (tables, files, etc.)
     */
    public function uninstall(bool $deleteData = false): void;

    /**
     * Called on every request when plugin is active.
     * Use this for:
     * - Registering routes
     * - Binding services
     * - Any per-request initialization
     */
    public function boot(): void;

    /**
     * Get the plugin's migrations path.
     * Return null if plugin has no migrations.
     */
    public function migrationsPath(): ?string;

    /**
     * Get the plugin's config path.
     * Return null if plugin has no config.
     */
    public function configPath(): ?string;

    /**
     * Get the plugin's views path.
     * Return null if plugin has no views.
     */
    public function viewsPath(): ?string;

    /**
     * Get the plugin's web routes path.
     * Return null if plugin has no web routes.
     */
    public function webRoutesPath(): ?string;

    /**
     * Get the plugin's API routes path.
     * Return null if plugin has no API routes.
     */
    public function apiRoutesPath(): ?string;

    /**
     * Get the plugin's translations path.
     * Return null if plugin has no translations.
     */
    public function translationsPath(): ?string;

    /**
     * Get the plugin's Filament resources path.
     * Return null if plugin has no Filament resources.
     */
    public function filamentResourcesPath(): ?string;

    /**
     * Get the plugin's Filament pages path.
     * Return null if plugin has no Filament pages.
     */
    public function filamentPagesPath(): ?string;

    /**
     * Get the plugin's Filament widgets path.
     * Return null if plugin has no Filament widgets.
     */
    public function filamentWidgetsPath(): ?string;

    /**
     * Get the plugin's namespace.
     * Used for auto-discovering Filament components.
     */
    public function namespace(): ?string;
}
