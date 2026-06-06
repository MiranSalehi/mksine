<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'mksine:create-super-admin', description: 'Create a user and assign the Shield super admin role (creating the role and syncing all permissions when needed)')]
class CreateSuperAdminCommand extends Command
{
    protected $signature = 'mksine:create-super-admin
                            {--name= : Super admin display name}
                            {--email= : Super admin email}
                            {--password= : Super admin password (min 8 characters)}
                            {--panel=admin : Filament panel ID (Shield guard context)}
                            {--tenant= : Team/Tenant ID when Shield tenancy is enabled}';

    protected $description = 'Create a super admin user on the application database, ensure the super_admin role exists with all permissions, and assign that role to the user.';

    public function handle(): int
    {
        if (! Utils::isSuperAdminEnabled()) {
            $this->error('Super admin is disabled in config/filament-shield.php (super_admin.enabled).');

            return self::FAILURE;
        }

        $panelId = (string) $this->option('panel');
        if ($panelId === '') {
            $panelId = 'admin';
        }

        Filament::setCurrentPanel($panelId);

        $tenantId = $this->option('tenant');

        if (Utils::isTenancyEnabled()) {
            if (blank($tenantId)) {
                $this->error('Shield tenancy (teams) is enabled. Pass --tenant= with the team/tenant ID.');

                return self::FAILURE;
            }

            setPermissionsTeamId($tenantId);
        }

        $name = $this->resolveName();
        if ($name === null) {
            return self::FAILURE;
        }

        $email = $this->resolveEmail();
        if ($email === null) {
            return self::FAILURE;
        }

        $plainPassword = $this->resolvePassword();
        if ($plainPassword === null) {
            return self::FAILURE;
        }

        $userClass = config('auth.providers.users.model');
        if (! is_string($userClass) || ! is_subclass_of($userClass, Model::class)) {
            $this->error('config auth.providers.users.model must be an Eloquent model class.');

            return self::FAILURE;
        }

        if ($userClass::query()->where('email', $email)->exists()) {
            $this->error('A user with this email already exists.');

            return self::FAILURE;
        }

        /** @var Model $user */
        $user = $userClass::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
        ]);

        if (array_key_exists('email_verified_at', $user->getCasts())) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $superAdminRole = Utils::isTenancyEnabled()
            ? Utils::createRole(tenantId: $tenantId)
            : Utils::createRole();

        $superAdminRole->syncPermissions(
            Utils::getPermissionModel()::query()->pluck('id')
        );

        $user->unsetRelation('roles')->unsetRelation('permissions');
        $user->assignRole($superAdminRole);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $loginUrl = Filament::getPanel($panelId)?->getLoginUrl() ?? url('/admin/login');

        $this->newLine();
        $this->info('Super admin user created.');
        $this->line('  Login: '.$loginUrl);
        $this->line('  Email: '.$email);
        $this->line('  Role: '.Utils::getSuperAdminName());

        return self::SUCCESS;
    }

    private function resolveName(): ?string
    {
        $name = $this->option('name');
        if (is_string($name) && $name !== '') {
            return $name;
        }

        return text(label: 'Super admin name', required: true);
    }

    private function resolveEmail(): ?string
    {
        $email = $this->option('email');
        if (is_string($email) && $email !== '') {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Invalid --email.');

                return null;
            }

            return $email;
        }

        return text(
            label: 'Super admin email',
            required: true,
            validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL)
                ? null
                : 'Enter a valid email address.',
        );
    }

    private function resolvePassword(): ?string
    {
        $plainPassword = $this->option('password');
        if (is_string($plainPassword) && $plainPassword !== '') {
            if (strlen($plainPassword) < 8) {
                $this->error('Password must be at least 8 characters.');

                return null;
            }

            return $plainPassword;
        }

        return password(
            label: 'Super admin password',
            required: true,
            validate: fn (string $value): ?string => strlen($value) < 8
                ? 'Password must be at least 8 characters.'
                : null,
        );
    }
}
