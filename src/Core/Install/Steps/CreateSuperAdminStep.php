<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Support\MksineUserModelRequirements;

class CreateSuperAdminStep extends AbstractInstallStep
{
    public const DEFAULT_NAME = 'Admin';

    public function key(): string
    {
        return 'super-admin';
    }

    public function run(InstallContext $context): StepResult
    {
        if (! $context->wantsSuperAdmin()) {
            return StepResult::skipped($this->message('super_admin_not_requested'));
        }

        if (! $context->migrate || ! $this->databaseIsReady()) {
            return StepResult::skipped($this->message('database_not_ready'));
        }

        if (! MksineUserModelRequirements::isSatisfied()) {
            return StepResult::skipped(
                $this->message('super_admin_user_model_reloaded'),
                [$this->message('super_admin_run_manually', ['email' => (string) $context->adminEmail])],
            );
        }

        if ($this->userExists((string) $context->adminEmail)) {
            return StepResult::skipped($this->message('super_admin_exists', ['email' => (string) $context->adminEmail]));
        }

        // The command prompts for a missing name, which fails without a terminal.
        $arguments = [
            '--email' => $context->adminEmail,
            '--password' => $context->adminPassword,
            '--name' => filled($context->adminName) ? $context->adminName : self::DEFAULT_NAME,
            '--no-interaction' => true,
        ];

        [$code, $output] = $this->artisan('mksine:create-super-admin', $arguments);

        return $code === 0
            ? StepResult::completed([$this->message('super_admin_created', ['email' => (string) $context->adminEmail])])
            : StepResult::failed($this->message('command_failed', ['command' => 'mksine:create-super-admin']) . ' ' . $output);
    }

    private function userExists(string $email): bool
    {
        /** @var class-string<Model> $userClass */
        $userClass = (string) config('mksine.user_model', config('auth.providers.users.model'));

        return class_exists($userClass) && $userClass::query()->where('email', $email)->exists();
    }
}
