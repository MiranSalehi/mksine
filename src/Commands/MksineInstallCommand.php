<?php

namespace Miran\Mksine\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MksineInstallCommand extends Command
{
    public $signature = 'mksine:install {--migrate : Run migrations after publishing} {--force : Overwrite existing files}';

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

        // Run migrations if requested
        if ($this->option('migrate')) {
            $this->info('🔄 Running migrations...');
            $this->call('migrate');
            $this->info('   ✓ Migrations completed.');
        } else {
            $this->info('💡 Tip: Run `php artisan migrate` to create the database tables.');
        }

        $this->newLine();
        $this->info('✅ MKSine installed successfully!');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Review the config file: <comment>config/mksine.php</comment>');
        $this->line('  2. Run migrations: <comment>php artisan migrate</comment>');
        $this->line('  3. Check CMS info: <comment>php artisan mksine:info</comment>');

        return self::SUCCESS;
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
