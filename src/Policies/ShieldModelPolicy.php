<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Default policy for a model managed through a Shield resource.
 *
 * Every ability maps to the Shield permission of the same name (`viewAny` → `ViewAny:Post` with
 * the default pascal case and `:` separator), so a panel user without that permission is denied.
 * The package registers these only when the app has no `App\Policies\{Model}Policy`
 * ({@see PolicyRegistrar}); without any policy Filament would allow every action.
 */
abstract class ShieldModelPolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return $this->allows($user, 'viewAny');
    }

    public function view(Authorizable $user, Model $record): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(Authorizable $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(Authorizable $user, Model $record): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(Authorizable $user, Model $record): bool
    {
        return $this->allows($user, 'delete');
    }

    /**
     * Shield does not generate `DeleteAny` by default, so bulk delete also accepts `Delete`.
     */
    public function deleteAny(Authorizable $user): bool
    {
        return $this->allows($user, 'deleteAny') || $this->allows($user, 'delete');
    }

    public function restore(Authorizable $user, Model $record): bool
    {
        return $this->allows($user, 'restore');
    }

    public function restoreAny(Authorizable $user): bool
    {
        return $this->allows($user, 'restoreAny');
    }

    public function forceDelete(Authorizable $user, Model $record): bool
    {
        return $this->allows($user, 'forceDelete');
    }

    public function forceDeleteAny(Authorizable $user): bool
    {
        return $this->allows($user, 'forceDeleteAny');
    }

    public function replicate(Authorizable $user, Model $record): bool
    {
        return $this->allows($user, 'replicate');
    }

    public function reorder(Authorizable $user): bool
    {
        return $this->allows($user, 'reorder');
    }

    /**
     * Shield permission name for an ability, e.g. `ViewAny:Post`.
     */
    public function permissionFor(string $ability): string
    {
        return static::permissionKey($ability, $this->permissionSubject());
    }

    /**
     * Builds the key the way Shield does from `filament-shield.permissions` (separator and case).
     */
    public static function permissionKey(string $ability, string $subject): string
    {
        $separator = config('filament-shield.permissions.separator', ':');
        $case = config('filament-shield.permissions.case', 'pascal');

        return FilamentShield::defaultPermissionKeyBuilder(
            affix: $ability,
            separator: is_string($separator) ? $separator : ':',
            subject: $subject,
            case: is_string($case) ? $case : 'pascal',
        );
    }

    /**
     * Shield subject (the model's class basename): `PostPolicy` → `Post`.
     */
    protected function permissionSubject(): string
    {
        return Str::beforeLast(class_basename(static::class), 'Policy');
    }

    protected function allows(Authorizable $user, string $ability): bool
    {
        return $user->can($this->permissionFor($ability));
    }
}
