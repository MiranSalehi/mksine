<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization guard for updater actions.
 *
 * Updates rewrite code on disk and can run migrations; they must be restricted
 * to the Shield Super Admin role. We honour whatever role name is configured
 * at config('filament-shield.super_admin.name') (default: "super_admin").
 *
 * Usage in Filament actions/pages:
 *     SuperAdminGate::authorize();
 *
 * Throws AuthorizationException (which Filament renders as a 403 page).
 */
final class SuperAdminGate
{
    public static function check(?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();

        if ($user === null) {
            return false;
        }

        $role = config('filament-shield.super_admin.name', 'super_admin');

        if (! is_string($role) || $role === '') {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            /** @var mixed $result */
            $result = $user->hasRole($role);

            return (bool) $result;
        }

        return false;
    }

    public static function authorize(?Authenticatable $user = null): void
    {
        if (! self::check($user)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Only Super Admins may run MKSine updates.'
            );
        }
    }
}
