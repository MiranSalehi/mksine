<?php

declare(strict_types=1);

use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Panel;
use Illuminate\Support\Collection;
use Miran\Mksine\Concerns\InteractsWithMksine;
use Miran\Mksine\Core\Hooks\HookFilterRegistry;
use Miran\Mksine\Core\Hooks\Hooks;

class MksinePanelAccessProbe
{
    use InteractsWithMksine;

    public function __construct(
        private readonly bool $superAdmin = false,
        private readonly bool $panelUser = false,
        private readonly bool $hasPermissions = false,
    ) {}

    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($roles === Utils::getSuperAdminName()) {
            return $this->superAdmin;
        }

        if ($roles === Utils::getPanelUserRoleName()) {
            return $this->panelUser;
        }

        return false;
    }

    public function getAllPermissions(): Collection
    {
        return $this->hasPermissions
            ? new Collection([(object) ['name' => 'probe']])
            : new Collection;
    }
}

describe('canAccessPanel hook filter', function () {
    it('closes access when the filter returns false', function (): void {
        Hooks::addFilter('mksine.user.can_access_panel', function (mixed $decision, object $user, Panel $panel): mixed {
            if ($panel->getId() === 'plugin-owned') {
                return false;
            }

            return $decision;
        });

        $user = new MksinePanelAccessProbe(superAdmin: true);
        $owned = Panel::make()->id('plugin-owned');
        $admin = Panel::make()->id('admin');

        expect($user->canAccessPanel($owned))->toBeFalse()
            ->and($user->canAccessPanel($admin))->toBeTrue();
    });

    it('keeps the current panel checks when the filter returns null', function (): void {
        Hooks::addFilter('mksine.user.can_access_panel', fn (mixed $decision): mixed => $decision);

        $admin = Panel::make()->id('admin');

        expect((new MksinePanelAccessProbe(superAdmin: true))->canAccessPanel($admin))->toBeTrue()
            ->and((new MksinePanelAccessProbe(hasPermissions: true))->canAccessPanel($admin))->toBeTrue()
            ->and((new MksinePanelAccessProbe)->canAccessPanel($admin))->toBeFalse();
    });

    it('does not throw when the hook filter registry is not bound', function (): void {
        app()->offsetUnset(HookFilterRegistry::class);

        $admin = Panel::make()->id('admin');

        expect(fn () => (new MksinePanelAccessProbe(superAdmin: true))->canAccessPanel($admin))
            ->not->toThrow(Throwable::class)
            ->and((new MksinePanelAccessProbe(superAdmin: true))->canAccessPanel($admin))->toBeTrue()
            ->and((new MksinePanelAccessProbe)->canAccessPanel($admin))->toBeFalse();
    });
});
