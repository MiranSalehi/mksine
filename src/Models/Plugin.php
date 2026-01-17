<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plugin model for tracking plugin state in database.
 *
 * Note: Metadata (name, version, author, etc.) comes from plugin.php manifest,
 * NOT from database. This model only tracks runtime state.
 */
class Plugin extends Model
{
    protected $table = 'mks_plugins';

    protected $fillable = [
        'plugin_id',
        'status',
        'settings',
        'installed_at',
        'activated_at',
        'deactivated_at',
        'boot_failed',
        'boot_error',
        'boot_failed_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'installed_at' => 'datetime',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'boot_failed' => 'boolean',
        'boot_failed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_INSTALLED = 'installed';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * Check if plugin is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if plugin is installed but not active.
     */
    public function isInstalled(): bool
    {
        return $this->status === self::STATUS_INSTALLED;
    }

    /**
     * Check if plugin is inactive (was active before).
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Check if plugin boot failed.
     */
    public function hasBootFailed(): bool
    {
        return $this->boot_failed === true;
    }

    /**
     * Mark plugin as boot failed.
     */
    public function markBootFailed(string $error): void
    {
        $this->update([
            'boot_failed' => true,
            'boot_error' => $error,
            'boot_failed_at' => now(),
            'status' => self::STATUS_INACTIVE,
        ]);
    }

    /**
     * Clear boot failure flag.
     */
    public function clearBootFailure(): void
    {
        $this->update([
            'boot_failed' => false,
            'boot_error' => null,
            'boot_failed_at' => null,
        ]);
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Scope: only active plugins.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: only plugins that didn't fail boot.
     */
    public function scopeBootable($query)
    {
        return $query->where('boot_failed', false);
    }
}
