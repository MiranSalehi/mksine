<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Filament\Models\Contracts\FilamentUser;
use Spatie\Permission\Traits\HasRoles;

final class MksineUserModelRequirements
{
    /**
     * @return list<string> Missing requirement labels (empty when satisfied).
     */
    public static function missingRequirements(?string $userClass = null): array
    {
        $userClass ??= (string) config('mksine.user_model', \App\Models\User::class);

        if (! is_string($userClass) || $userClass === '' || ! class_exists($userClass)) {
            return ['A valid user model class (config mksine.user_model)'];
        }

        $missing = [];

        if (! in_array(HasRoles::class, class_uses_recursive($userClass), true)) {
            $missing[] = 'Spatie HasRoles trait (or Miran\\Mksine\\Concerns\\InteractsWithMksine)';
        }

        if (! is_subclass_of($userClass, FilamentUser::class)) {
            $missing[] = 'FilamentUser contract (canAccessPanel)';
        }

        return $missing;
    }

    public static function isSatisfied(?string $userClass = null): bool
    {
        return self::missingRequirements($userClass) === [];
    }
}
