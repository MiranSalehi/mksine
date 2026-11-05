<?php

declare(strict_types=1);

namespace Miran\Mksine\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Miran\Mksine\Core\Install\Contracts\InstallStep;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\InstallPipeline;
use Miran\Mksine\Core\Install\InstallProfile;
use Miran\Mksine\Core\Install\InstallRunResult;
use Miran\Mksine\Core\Install\InstallStepStatus;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Plugins\PluginManifest;
use Miran\Mksine\Core\Theme\ThemeData;
use Miran\Mksine\Core\Theme\ThemeManager;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Command-line front end for the shared install pipeline (dedicated servers and local development).
 */
class MksineInstallCommand extends Command
{
    public const GITHUB_URL = 'https://github.com/miransalehi/mksine';

    /**
     * --db-* option => .env key.
     *
     * @var array<string, string>
     */
    private const ENVIRONMENT_OPTIONS = [
        'app-url' => 'APP_URL',
        'db-connection' => 'DB_CONNECTION',
        'db-host' => 'DB_HOST',
        'db-port' => 'DB_PORT',
        'db-database' => 'DB_DATABASE',
        'db-username' => 'DB_USERNAME',
        'db-password' => 'DB_PASSWORD',
    ];

    public $signature = 'mksine:install
                            {--migrate : Run migrations and finish setup}
                            {--force : Overwrite existing published files}
                            {--admin-email= : Create a super admin with this email (requires --admin-password and --migrate)}
                            {--admin-password= : Super admin password (min 8 characters)}
                            {--admin-name= : Super admin display name}
                            {--profile= : vps or shared (detected when omitted)}
                            {--theme= : Theme to activate (the default theme when omitted)}
                            {--plugins= : Comma-separated plugin IDs to activate}
                            {--locale= : Site language, e.g. fa or en}
                            {--seed-demo : Add sample pages, posts and menus}
                            {--app-url= : Write APP_URL to .env}
                            {--db-connection= : Write DB_CONNECTION to .env}
                            {--db-host= : Write DB_HOST to .env}
                            {--db-port= : Write DB_PORT to .env}
                            {--db-database= : Write DB_DATABASE to .env}
                            {--db-username= : Write DB_USERNAME to .env}
                            {--db-password= : Write DB_PASSWORD to .env}
                            {--resume : Continue an interrupted installation instead of starting over}';

    public $description = 'Install MKSine: check requirements, publish files, migrate, activate theme and plugins, create the admin';

    public function handle(InstallPipeline $pipeline): int
    {
        $this->info('🚀 Installing MKSine...');
        $this->newLine();

        $context = $this->shouldRunWizard() ? $this->askForContext() : $this->contextFromOptions();

        if (! $this->option('resume')) {
            $pipeline->progress()->clear();
        }

        $this->line('  ' . __('mksine::install.profiles.' . $context->profile->value));
        $this->newLine();

        $result = $pipeline->run($context, fn (InstallStep $step, StepResult $stepResult) => $this->reportStep($step, $stepResult));

        $this->newLine();

        if (! $result->succeeded()) {
            $this->error('✗ MKSine installation stopped at step [' . $result->failedStepKey() . '].');
            $this->line('  Fix the problem above, then run <comment>php artisan mksine:install --resume' . ($context->migrate ? ' --migrate' : '') . '</comment>.');

            return self::FAILURE;
        }

        $this->info('✅ MKSine installed successfully!');
        $this->newLine();
        $this->displayNextSteps($context, $result);

        $this->askToStarRepository();

        return self::SUCCESS;
    }

    protected function contextFromOptions(): InstallContext
    {
        return new InstallContext(
            profile: $this->resolveProfile(),
            migrate: (bool) $this->option('migrate'),
            force: (bool) $this->option('force'),
            adminName: $this->stringOption('admin-name'),
            adminEmail: $this->stringOption('admin-email'),
            adminPassword: $this->stringOption('admin-password'),
            theme: $this->stringOption('theme'),
            plugins: $this->pluginOption(),
            locale: $this->stringOption('locale'),
            seedSampleContent: (bool) $this->option('seed-demo'),
            environment: $this->environmentOptions(),
        );
    }

    /**
     * Interactive install: ask for whatever the options did not already answer.
     */
    protected function askForContext(): InstallContext
    {
        $defaults = $this->contextFromOptions();
        $migrate = $defaults->migrate || confirm('Run database migrations and finish setup now?', default: true);

        $adminEmail = $defaults->adminEmail;
        $adminPassword = $defaults->adminPassword;
        $adminName = $defaults->adminName;

        if ($migrate && $adminEmail === null && confirm('Create a super admin account?', default: true)) {
            $adminName = text('Admin name', default: 'Admin', required: true);
            $adminEmail = text('Admin email', required: true, validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email address.');
            $adminPassword = password('Admin password', required: true, validate: fn (string $value): ?string => strlen($value) >= 8 ? null : 'Use at least 8 characters.');
        }

        return new InstallContext(
            profile: $defaults->profile,
            migrate: $migrate,
            force: $defaults->force,
            adminName: $adminName,
            adminEmail: $adminEmail,
            adminPassword: $adminPassword,
            theme: $defaults->theme ?? $this->askForTheme(),
            plugins: $defaults->plugins !== [] ? $defaults->plugins : $this->askForPlugins(),
            locale: $defaults->locale ?? select('Site language', ['fa' => 'فارسی (fa)', 'en' => 'English (en)'], default: (string) config('app.locale', 'en')),
            seedSampleContent: $defaults->seedSampleContent || ($migrate && confirm('Add sample pages, posts and menus?', default: false)),
            environment: $defaults->environment,
        );
    }

    protected function shouldRunWizard(): bool
    {
        return $this->input->isInteractive() && ! $this->laravel->runningUnitTests();
    }

    private function askForTheme(): ?string
    {
        try {
            $themes = app(ThemeManager::class)->discover()
                ->mapWithKeys(fn (ThemeData $theme): array => [$theme->identifier => $theme->name])
                ->all();
        } catch (Throwable) {
            return null;
        }

        if (count($themes) < 2) {
            return null;
        }

        return select('Theme', $themes, default: array_key_exists('mksine', $themes) ? 'mksine' : array_key_first($themes));
    }

    /**
     * @return list<string>
     */
    private function askForPlugins(): array
    {
        try {
            $plugins = collect(app(PluginManager::class)->discover())
                ->mapWithKeys(fn (PluginManifest $manifest): array => [$manifest->id() => $manifest->name()])
                ->all();
        } catch (Throwable) {
            return [];
        }

        if ($plugins === []) {
            return [];
        }

        return array_values(array_map('strval', multiselect('Plugins to activate', $plugins, scroll: 12)));
    }

    private function resolveProfile(): InstallProfile
    {
        $option = $this->stringOption('profile');

        if ($option !== null) {
            return InstallProfile::tryFrom($option) ?? throw new \InvalidArgumentException("Unknown profile [{$option}]. Use vps or shared.");
        }

        return app(EnvironmentProbe::class)->probe(public_path())->recommendedProfile();
    }

    /**
     * @return list<string>
     */
    private function pluginOption(): array
    {
        $plugins = $this->stringOption('plugins');

        return $plugins === null ? [] : array_values(array_filter(array_map('trim', explode(',', $plugins))));
    }

    /**
     * @return array<string, string>
     */
    private function environmentOptions(): array
    {
        $values = [];

        foreach (self::ENVIRONMENT_OPTIONS as $option => $envKey) {
            $value = $this->option($option);

            if (is_string($value) && $value !== '') {
                $values[$envKey] = $value;
            }
        }

        return $values;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function reportStep(InstallStep $step, StepResult $result): void
    {
        match ($result->status) {
            InstallStepStatus::Completed => $this->line('  <info>✓</info> ' . $step->label()),
            InstallStepStatus::Skipped => $this->line('  <comment>–</comment> ' . $step->label() . ' <fg=gray>(' . ($result->messages[0] ?? '') . ')</>'),
            InstallStepStatus::Failed => $this->line('  <error>✗</error> ' . $step->label() . ': ' . $result->error),
            InstallStepStatus::Incomplete => null,
        };

        if ($result->status === InstallStepStatus::Completed) {
            foreach ($result->messages as $message) {
                $this->line('     <fg=gray>' . $message . '</>');
            }
        }

        foreach ($result->warnings as $warning) {
            $this->warn('     ! ' . $warning);
        }
    }

    protected function displayNextSteps(InstallContext $context, InstallRunResult $result): void
    {
        $this->line('Next steps:');

        if (! $context->migrate) {
            $this->line('  1. Finish setup: <comment>php artisan mksine:install --migrate</comment>');
            $this->line('  2. Check CMS info: <comment>php artisan mksine:info</comment>');

            return;
        }

        $adminCreated = ($result->results['super-admin'] ?? null)?->status === InstallStepStatus::Completed;

        if (! $adminCreated) {
            $this->line('  • Create a super admin: <comment>php artisan mksine:create-super-admin</comment>');
        }

        $this->line('  • Log in at <comment>' . url('/admin') . '</comment>');
        $this->line('  • Check CMS info: <comment>php artisan mksine:info</comment>');
    }

    /**
     * Invites the user to star MKSine on GitHub (interactive installs only; skipped in CI and tests).
     */
    protected function askToStarRepository(): void
    {
        if (! $this->shouldAskToStar()) {
            return;
        }

        $this->newLine();

        if (! $this->confirmStar()) {
            return;
        }

        $this->openUrlInBrowser(self::GITHUB_URL);
    }

    protected function confirmStar(): bool
    {
        return confirm(
            label: '🎉 MKSine is ready! Would you like to support the project by starring it on GitHub?',
            default: true,
        );
    }

    protected function shouldAskToStar(): bool
    {
        return $this->input->isInteractive() && ! $this->laravel->runningUnitTests();
    }

    protected function openUrlInBrowser(string $url): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => ['open', $url],
            'Windows' => ['cmd', '/c', 'start', '', $url],
            default => ['xdg-open', $url],
        };

        try {
            $succeeded = Process::run($command)->successful();
        } catch (Throwable) {
            $succeeded = false;
        }

        if ($succeeded) {
            $this->info('💜 Thank you!');

            return;
        }

        $this->line('  Open this link in your browser: <href=' . $url . '>' . $url . '</>');
    }
}
