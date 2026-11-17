<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Filament\Resources\Users\UserResource;

/**
 * Default policy for the user resource (`ViewAny:User`, `Update:User`, ...).
 *
 * The Shield subject is the configured user model's class name (`mksine.user_model`), the same
 * subject Shield generates for {@see UserResource}.
 * Registered only when the app and plugins have no policy for that model.
 */
class UserPolicy extends ShieldModelPolicy
{
    protected function permissionSubject(): string
    {
        return class_basename(PolicyRegistrar::userModel());
    }
}
