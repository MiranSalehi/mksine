<?php

declare(strict_types=1);

namespace Miran\Mksine\Concerns;

use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Panel;
use Miran\Mksine\Core\Hooks\HookFilterRegistry;
use Miran\Mksine\Core\Hooks\Hooks;
use Spatie\Permission\Traits\HasRoles;

/**
 * Drop-in trait that gives an application's User model everything MKSine needs to
 * authorize the Filament admin panel: Spatie roles/permissions and a Shield-aware
 * {@see canAccessPanel()} implementation.
 *
 * Add it to your User model together with the {@see \Filament\Models\Contracts\FilamentUser}
 * contract:
 *
 * ```php
 * use Filament\Models\Contracts\FilamentUser;
 * use Miran\Mksine\Concerns\InteractsWithMksine;
 *
 * class User extends Authenticatable implements FilamentUser
 * {
 *     use InteractsWithMksine;
 * }
 * ```
 *
 * Intentionally free of Fortify/media dependencies so it works on any Laravel app
 * that installed `miran/mksine` via Composer.
 */
trait InteractsWithMksine
{
    use HasRoles;

    /**
     * Filter name for plugin-owned Filament panel access.
     *
     * A listener must return a bool only when `$panel` belongs to that plugin.
     * For every other panel it must return the incoming value unchanged. Returning
     * `true` for a panel the listener does not own grants access to every panel,
     * because the first bool is used and the Shield checks below do not run.
     * `null` means the listener does not claim the panel.
     */
    public const CAN_ACCESS_PANEL_FILTER = 'mksine.user.can_access_panel';

    /**
     * Whether this user may open `$panel`.
     *
     * Plugins decide access for their own panel through
     * {@see CAN_ACCESS_PANEL_FILTER}. A listener must return a bool only when the
     * panel belongs to that plugin, and must return the incoming value for every
     * other panel. Returning `true` for an unknown panel grants access to every
     * panel. `null` (or any non-bool) means the panel was not claimed, and the
     * super-admin, panel-user, and permission checks below run unchanged.
     *
     * The filter is skipped when {@see HookFilterRegistry} is not bound, so a
     * container that has not booted hooks cannot take down panel login.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (app()->bound(HookFilterRegistry::class)) {
            $decision = Hooks::filter(self::CAN_ACCESS_PANEL_FILTER, null, $this, $panel);

            if (is_bool($decision)) {
                return $decision;
            }
        }

        if ($this->hasRole(Utils::getSuperAdminName())) {
            return true;
        }

        if (Utils::isPanelUserRoleEnabled() && $this->hasRole(Utils::getPanelUserRoleName())) {
            return true;
        }

        return $this->getAllPermissions()->isNotEmpty();
    }
}
