<?php

declare(strict_types=1);

namespace Miran\Mksine\Concerns;

use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Panel;
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

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole(Utils::getSuperAdminName())) {
            return true;
        }

        if (Utils::isPanelUserRoleEnabled() && $this->hasRole(Utils::getPanelUserRoleName())) {
            return true;
        }

        return $this->getAllPermissions()->isNotEmpty();
    }
}
