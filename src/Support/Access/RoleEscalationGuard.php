<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Core\Updater\SuperAdminGate;

/**
 * Keeps the Super Admin role out of reach of everyone who is not already a Super Admin.
 *
 * Without this, any role holding `Update:User` is effectively a Super Admin: it can tick
 * the super admin checkbox on its own account, or reset an existing Super Admin's
 * password and sign in as them. Both paths are closed here rather than in the app's
 * generated Shield policy, which is regenerated on every `shield:generate`.
 */
final class RoleEscalationGuard
{
    /**
     * Role names that only a Super Admin may grant, revoke, or manage the holders of.
     *
     * @return list<string>
     */
    public static function protectedRoleNames(): array
    {
        $superAdmin = config('filament-shield.super_admin.name', 'super_admin');

        return is_string($superAdmin) && $superAdmin !== '' ? [$superAdmin] : [];
    }

    public static function canManageProtectedRoles(?Authenticatable $user = null): bool
    {
        return SuperAdminGate::check($user);
    }

    /**
     * Whether the given user record holds a protected role.
     */
    public static function isProtectedUser(?Model $record): bool
    {
        if ($record === null || ! method_exists($record, 'hasRole')) {
            return false;
        }

        $protected = self::protectedRoleNames();

        return $protected !== [] && (bool) $record->hasRole($protected);
    }

    /**
     * Whether the current user is allowed to modify the given user record.
     */
    public static function canManageUser(?Model $record): bool
    {
        return ! self::isProtectedUser($record) || self::canManageProtectedRoles();
    }
}
