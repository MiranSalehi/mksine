<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Miran\Mksine\Filament\Resources\Users\Schemas\UserForm;
use Miran\Mksine\Filament\Resources\Users\UserResource;
use Miran\Mksine\Support\Access\RoleEscalationGuard;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * Filament schemas must belong to a Livewire component; this is the smallest one that
 * satisfies the contract so the user form can be built outside a real panel request.
 */
final class RoleEscalationSchemaHarness extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public function render(): string
    {
        return '<div></div>';
    }
}

function superAdminRoleName(): string
{
    return config('filament-shield.super_admin.name', 'super_admin');
}

function makeUserWithRole(string $role): User
{
    Role::findOrCreate($role, 'web');

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * The `roles` checkbox list a `UserForm` would render for the current user.
 */
function userFormRolesComponent(): CheckboxList
{
    $schema = UserForm::configure(Schema::make(new RoleEscalationSchemaHarness)->model(User::class));

    foreach ($schema->getFlatComponents(withHidden: true) as $component) {
        if ($component instanceof CheckboxList && $component->getName() === 'roles') {
            return $component;
        }
    }

    throw new RuntimeException('The user form no longer exposes a roles checkbox list.');
}

/**
 * @return list<string>
 */
function availableRoleOptions(): array
{
    return array_values(userFormRolesComponent()->getOptions());
}

beforeEach(function (): void {
    $this->editor = makeUserWithRole('ztest-editor');
    $this->superAdmin = makeUserWithRole(superAdminRoleName());
});

it('hides the super admin role from users who do not hold it', function (): void {
    $this->actingAs($this->editor);

    expect(availableRoleOptions())
        ->toContain('ztest-editor')
        ->not->toContain(superAdminRoleName());
});

it('offers the super admin role to super admins', function (): void {
    $this->actingAs($this->superAdmin);

    expect(availableRoleOptions())->toContain(superAdminRoleName());
});

it('validates submitted roles against the visible options only', function (): void {
    $this->actingAs($this->editor);

    $rules = [];
    userFormRolesComponent()->dehydrateValidationRules($rules);

    $editorId = Role::where('name', 'ztest-editor')->value('id');
    $superAdminId = Role::where('name', superAdminRoleName())->value('id');

    expect(validator(['roles' => [(string) $superAdminId]], $rules)->fails())->toBeTrue()
        ->and(validator(['roles' => [(string) $editorId]], $rules)->fails())->toBeFalse();
});

describe('protecting super admin accounts from takeover', function (): void {
    it('reports a super admin record as protected', function (): void {
        expect(RoleEscalationGuard::isProtectedUser($this->superAdmin))->toBeTrue()
            ->and(RoleEscalationGuard::isProtectedUser($this->editor))->toBeFalse();
    });

    it('stops a non super admin from managing a super admin record', function (): void {
        $this->actingAs($this->editor);

        expect(RoleEscalationGuard::canManageUser($this->superAdmin))->toBeFalse()
            ->and(RoleEscalationGuard::canManageUser($this->editor))->toBeTrue();
    });

    it('lets a super admin manage any record', function (): void {
        $this->actingAs($this->superAdmin);

        expect(RoleEscalationGuard::canManageUser($this->superAdmin))->toBeTrue()
            ->and(RoleEscalationGuard::canManageUser($this->editor))->toBeTrue();
    });

    it('blocks editing and deleting a super admin through the resource', function (): void {
        $this->editor->givePermissionTo(
            Permission::findOrCreate('Update:User', 'web'),
            Permission::findOrCreate('Delete:User', 'web'),
        );

        $this->actingAs($this->editor);

        expect(UserResource::canEdit($this->superAdmin))->toBeFalse()
            ->and(UserResource::canDelete($this->superAdmin))->toBeFalse()
            ->and(UserResource::canEdit($this->editor))->toBeTrue();
    });
});
