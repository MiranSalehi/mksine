<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Miran\Mksine\Models\PageBuilderSection;

class PageBuilderSectionPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Page');
    }

    public function view(AuthUser $authUser, PageBuilderSection $section): bool
    {
        return $authUser->can('View:Page');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Page');
    }

    public function update(AuthUser $authUser, PageBuilderSection $section): bool
    {
        return $authUser->can('Update:Page');
    }

    public function delete(AuthUser $authUser, PageBuilderSection $section): bool
    {
        return $authUser->can('Delete:Page');
    }

    public function restore(AuthUser $authUser, PageBuilderSection $section): bool
    {
        return $authUser->can('Restore:Page');
    }

    public function forceDelete(AuthUser $authUser, PageBuilderSection $section): bool
    {
        return $authUser->can('ForceDelete:Page');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Page');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Page');
    }

    public function replicate(AuthUser $authUser, PageBuilderSection $section): bool
    {
        return $authUser->can('Replicate:Page');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Page');
    }
}
