<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Models\GeoCountry;

/**
 * Default policy for {@see GeoCountry}: countries come from the geo import,
 * so they can be viewed (`ViewAny:GeoCountry`, `View:GeoCountry`) but never changed in the panel.
 *
 * Used only when the app has no `App\Policies\GeoCountryPolicy`.
 */
class GeoCountryPolicy extends ShieldModelPolicy
{
    public function create(Authorizable $user): bool
    {
        return false;
    }

    public function update(Authorizable $user, Model $record): bool
    {
        return false;
    }

    public function delete(Authorizable $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(Authorizable $user): bool
    {
        return false;
    }

    public function restore(Authorizable $user, Model $record): bool
    {
        return false;
    }

    public function restoreAny(Authorizable $user): bool
    {
        return false;
    }

    public function forceDelete(Authorizable $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(Authorizable $user): bool
    {
        return false;
    }

    public function replicate(Authorizable $user, Model $record): bool
    {
        return false;
    }

    public function reorder(Authorizable $user): bool
    {
        return false;
    }
}
