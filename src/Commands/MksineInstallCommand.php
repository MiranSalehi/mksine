<?php

namespace Miran\Mksine\Commands;

use Illuminate\Console\Command;

class MksineInstallCommand extends Command
{
    public $signature = 'mksine:install {--migrate : Run migrations after publishing} {--force : Overwrite existing files}';

    public $description = 'Install MKSine package (publish config, migrations, etc.)';

    public function handle(): int
    {
        $this->info('🚀 Installing MKSine...');
        $this->newLine();

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
}
