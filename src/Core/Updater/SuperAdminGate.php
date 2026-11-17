<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

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

    /**
     * Super Admins pass every ability that nothing else decided.
     *
     * With Shield's `define_via_gate` off, the role only holds the permissions it was given, so
     * permissions created later (a new resource, an activated plugin) were missing and Super
     * Admins got 403s. This runs as a Gate "after" callback: a policy that explicitly answers
     * false (e.g. read-only geo countries) still wins; only undecided checks become true.
     */
    public static function registerGateFallback(): void
    {
        if (! (bool) config('filament-shield.super_admin.enabled', true)) {
            return;
        }

        if ((bool) config('filament-shield.super_admin.define_via_gate', false)) {
            return;
        }

        Gate::after(static function (Authenticatable $user, string $ability, mixed $result): ?bool {
            if ($result !== null) {
                return null;
            }

            return self::check($user) ? true : null;
        });
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
