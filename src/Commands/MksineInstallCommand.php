<?php

declare(strict_types=1);

namespace Miran\Mksine\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MksineInstallCommand extends Command
{
    public $signature = 'mksine:install
                            {--migrate : Run migrations after publishing}
                            {--force : Overwrite existing files}
                            {--admin-email= : Create a super admin with this email (requires --admin-password and --migrate)}
                            {--admin-password= : Super admin password (min 8 characters)}
                            {--admin-name= : Super admin display name}';

    public $description = 'Install MKSine package (publish config, migrations, etc.)';

    public function handle(): int
    {
        $this->info('🚀 Installing MKSine...');
        $this->newLine();

        // Copy User model to app/Models (only if missing, or when --force)
        $this->publishUserModel();

        // Publish config file
        $this->info('📄 Publishing configuration file...');
        $this->call('vendor:publish', [
            '--provider' => 'Miran\Mksine\MksineServiceProvider',
            '--tag' => 'mksine-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->info('📦 Publishing migrations...');
        $this->call('vendor:publish', [
            '--provider' => 'Miran\Mksine\MksineServiceProvider',
            '--tag' => 'mksine-migrations',
            '--force' => $this->option('force'),
        ]);

        // Publish translation files to project lang path
        $this->info('🌐 Publishing translation files...');
        $this->call('vendor:publish', [
            '--provider' => 'Miran\Mksine\MksineServiceProvider',
            '--tag' => 'mksine-lang',
            '--force' => $this->option('force'),
        ]);

        // Publish fonts (IranYekan, etc.)
        $this->info('🔤 Publishing fonts...');
        $this->call('vendor:publish', [
            '--provider' => 'Miran\Mksine\MksineServiceProvider',
            '--tag' => 'mksine-fonts',
            '--force' => $this->option('force'),
        ]);

        $this->publishShieldAuthorization();
        $this->clearInstallationCaches();

        $migrated = false;

        if ($this->option('migrate')) {
            $this->info('🔄 Running migrations...');
            $this->call('migrate');
            $this->info('   ✓ Migrations completed.');
            $migrated = true;
        } else {
            $this->info('💡 Tip: Run <comment>php artisan mksine:install --migrate</comment> to migrate and finish setup automatically.');
        }

        $this->finalizeInstallation($migrated);

        $this->newLine();
        $this->info('✅ MKSine installed successfully!');
        $this->newLine();
        $this->displayNextSteps($migrated);

        return self::SUCCESS;
    }

    protected function clearInstallationCaches(): void
    {
        $this->info('🧹 Clearing application caches...');

        foreach (['optimize:clear', 'filament:optimize-clear'] as $command) {
            try {
                $this->call($command);
            } catch (\Throwable $exception) {
                $this->warn("   ! {$command} failed: {$exception->getMessage()}");
            }
        }
    }

    protected function finalizeInstallation(bool $migrated): void
    {
        $this->publishFilamentAssets();

        if (! $this->databaseIsReady()) {
            return;
        }

        $this->generateShieldPermissions();
        $this->discoverHooks();
        $this->createSuperAdminIfRequested($migrated);
    }

    protected function publishFilamentAssets(): void
    {
        $this->info('🎨 Publishing Filament panel assets...');

        try {
            $this->call('filament:assets');
        } catch (\Throwable $exception) {
            $this->warn('   ! filament:assets failed: '.$exception->getMessage());
        }
    }

    protected function generateShieldPermissions(): void
    {
        if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShieldServiceProvider::class)) {
            return;
        }

        if (! $this->hasDatabaseTable('permissions')) {
            return;
        }

        if (! $this->adminPanelIsReadyForShield()) {
            $this->warn('   ! Skipping Shield generation — the admin panel is not ready.');
            $this->displayShieldPrerequisites();

            return;
        }

        $this->info('🛡 Generating Shield permissions and policies...');

        try {
            $exitCode = $this->call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--no-interaction' => true,
            ]);

            if ($exitCode !== self::SUCCESS) {
                $this->warn('   ! shield:generate exited with a non-zero status.');
            }
        } catch (\Throwable $exception) {
            $this->warn('   ! shield:generate failed: '.$exception->getMessage());
        }
    }

    protected function discoverHooks(): void
    {
        if (! $this->hasDatabaseTable('mks_hooks')) {
            return;
        }

        $this->info('🔗 Discovering hook listeners...');

        try {
            $exitCode = $this->call('mks:discover');

            if ($exitCode !== self::SUCCESS) {
                $this->warn('   ! mks:discover exited with a non-zero status.');
            }
        } catch (\Throwable $exception) {
            $this->warn('   ! mks:discover failed: '.$exception->getMessage());
        }
    }

    protected function createSuperAdminIfRequested(bool $migrated): void
    {
        $email = $this->option('admin-email');
        $password = $this->option('admin-password');
        $name = $this->option('admin-name');

        if (! is_string($email) || $email === '') {
            return;
        }

        if (! is_string($password) || $password === '') {
            $this->warn('   ! --admin-email was provided without --admin-password; skipping super admin creation.');

            return;
        }

        if (! $migrated) {
            $this->warn('   ! Super admin creation requires --migrate; run <comment>mksine:create-super-admin</comment> manually.');

            return;
        }

        $this->info('👤 Creating super admin user...');

        $arguments = [
            '--email' => $email,
            '--password' => $password,
            '--no-interaction' => true,
        ];

        if (is_string($name) && $name !== '') {
            $arguments['--name'] = $name;
        }

        try {
            $exitCode = $this->call('mksine:create-super-admin', $arguments);

            if ($exitCode !== self::SUCCESS) {
                $this->warn('   ! mksine:create-super-admin exited with a non-zero status.');
            }
        } catch (\Throwable $exception) {
            $this->warn('   ! mksine:create-super-admin failed: '.$exception->getMessage());
        }
    }

    protected function databaseIsReady(): bool
    {
        return $this->hasDatabaseTable('permissions')
            || $this->hasDatabaseTable('mks_hooks');
    }

    protected function adminPanelIsReadyForShield(): bool
    {
        try {
            return Filament::getPanel('admin')->hasPlugin('mksine');
        } catch (\Throwable) {
            return false;
        }
    }

    protected function displayShieldPrerequisites(): void
    {
        $this->line('     Register MKSine on the Filament panel first, then run:');
        $this->line('       <comment>php artisan shield:generate --all --panel=admin</comment>');
        $this->newLine();
        $this->line('     Prerequisites:');
        $this->line('       1. <comment>php artisan filament:install --panels</comment> (or <comment>make:filament-panel admin</comment>)');
        $this->line('       2. Add <comment>MksinePlugin::make()</comment> to <comment>app/Providers/Filament/AdminPanelProvider.php</comment>');
        $this->line('       3. Re-run <comment>php artisan mksine:install --migrate</comment> or run <comment>shield:generate --all</comment> manually');
    }

    protected function hasDatabaseTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function displayNextSteps(bool $migrated): void
    {
        $this->line('Next steps:');

        if (! $migrated) {
            $this->line('  1. Finish setup: <comment>php artisan mksine:install --migrate</comment>');
            $this->line('  2. Create a super admin: <comment>php artisan mksine:create-super-admin</comment>');
            $this->line('  3. (Optional) Review <comment>config/mksine.php</comment>.');
            $this->line('  4. Check CMS info: <comment>php artisan mksine:info</comment>');

            return;
        }

        $adminEmail = $this->option('admin-email');
        $adminPassword = $this->option('admin-password');
        $adminCreated = is_string($adminEmail) && $adminEmail !== ''
            && is_string($adminPassword) && $adminPassword !== '';

        if (! $adminCreated) {
            $step = 1;

            if ($migrated && ! $this->adminPanelIsReadyForShield()) {
                $this->line("  {$step}. Register <comment>MksinePlugin::make()</comment> on the admin panel (see installation docs).");
                $step++;
                $this->line("  {$step}. Generate permissions: <comment>php artisan shield:generate --all --panel=admin</comment>");
                $step++;
            }

            $this->line("  {$step}. Create a super admin: <comment>php artisan mksine:create-super-admin</comment>");
            $this->line('     Or pass <comment>--admin-email</comment> and <comment>--admin-password</comment> on install.');
            $step++;
            $this->line("  {$step}. (Optional) Review <comment>config/mksine.php</comment> — defaults work without extra .env keys.");
            $step++;
            $this->line("  {$step}. Check CMS info: <comment>php artisan mksine:info</comment>");

            return;
        }

        $this->line('  1. Log in at <comment>/admin</comment> with the super admin you created.');
        $this->line('  2. (Optional) Review <comment>config/mksine.php</comment>.');
        $this->line('  3. Check CMS info: <comment>php artisan mksine:info</comment>');
    }

    /**
     * Publish Filament Shield and Spatie Permission config + migrations (roles/permissions tables).
     *
     * Filament Shield does not ship its own migrations; it relies on spatie/laravel-permission.
     * Mirrors the non-interactive parts of `php artisan shield:setup`.
     */
    protected function publishShieldAuthorization(): void
    {
        if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShieldServiceProvider::class)) {
            $this->warn('🛡 Filament Shield is not installed; skipping Shield/permission publish.');

            return;
        }

        $force = (bool) $this->option('force');

        $this->info('🛡 Publishing Filament Shield & permission files...');

        if (! File::exists(config_path('filament-shield.php')) || $force) {
            $this->call('vendor:publish', [
                '--tag' => 'filament-shield-config',
                '--force' => $force,
            ]);
        } else {
            $this->line('   ✓ config/filament-shield.php already exists, skipping.');
        }

        if (! File::exists(config_path('permission.php')) || $force) {
            $this->call('vendor:publish', [
                '--tag' => 'permission-config',
                '--force' => $force,
            ]);
        } else {
            $this->line('   ✓ config/permission.php already exists, skipping.');
        }

        $this->call('vendor:publish', [
            '--tag' => 'permission-migrations',
            '--force' => $force,
        ]);

        $this->info('   ✓ Shield authorization migrations published.');
    }

    /**
     * Copy the package User model to app/Models/User.php.
     * Only copies when the file does not exist; use --force to overwrite.
     */
    protected function publishUserModel(): void
    {
        $this->info('👤 Publishing User model to app/Models/User.php...');

        $sourcePath = dirname(__DIR__) . '/Models/User.php';
        if (! File::exists($sourcePath)) {
            $this->warn('   Package User model not found, skipping.');

            return;
        }

        $targetDir = app_path('Models');
        $targetPath = $targetDir . '/User.php';

        if (File::exists($targetPath) && ! $this->option('force')) {
            $this->line('   ✓ User model already exists, skipping. Use <comment>--force</comment> to overwrite.');

            return;
        }

        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $content = File::get($sourcePath);
        $content = str_replace('namespace Miran\Mksine\Models;', 'namespace App\Models;', $content);

        File::put($targetPath, $content);
        $this->info('   ✓ User model published.');
    }
}
