<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Enums\GeoSource;
use Miran\Mksine\Models\GeoCity;

/**
 * Default policy for {@see GeoCity}. Cities are managed from the state screen, so the matching
 * `GeoState` permission also allows them; imported (non-manual) cities cannot be deleted.
 *
 * Used only when the app has no `App\Policies\GeoCityPolicy`.
 */
class GeoCityPolicy extends ShieldModelPolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return parent::viewAny($user) || $this->allowsState($user, 'viewAny');
    }

    public function view(Authorizable $user, Model $record): bool
    {
        return parent::view($user, $record) || $this->allowsState($user, 'viewAny');
    }

    public function create(Authorizable $user): bool
    {
        return parent::create($user) || $this->allowsState($user, 'update');
    }

    public function update(Authorizable $user, Model $record): bool
    {
        return parent::update($user, $record) || $this->allowsState($user, 'update');
    }

    public function delete(Authorizable $user, Model $record): bool
    {
        if ($record instanceof GeoCity && $record->source !== GeoSource::Manual) {
            return false;
        }

        return parent::delete($user, $record) || $this->allowsState($user, 'update');
    }

    public function deleteAny(Authorizable $user): bool
    {
        return parent::deleteAny($user) || $this->allowsState($user, 'update');
    }

    private function allowsState(Authorizable $user, string $ability): bool
    {
        return $user->can(static::permissionKey($ability, 'GeoState'));
    }
}
