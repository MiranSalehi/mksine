<?php

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Theme\ThemeManager;

class ThemePublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mks:theme-publish
                            {theme? : The theme identifier to publish (optional, publishes all if not specified)}
                            {--force : Overwrite existing published assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish theme assets (dist/, images/) to the public directory';

    /**
     * Execute the console command.
     */
    public function handle(ThemeManager $themeManager): int
    {
        $themeIdentifier = $this->argument('theme');
        $force = $this->option('force');

        if ($themeIdentifier) {
            return $this->publishTheme($themeManager, $themeIdentifier, $force);
        }

        return $this->publishAllThemes($themeManager, $force);
    }

    /**
     * Publish a single theme's assets.
     */
    protected function publishTheme(ThemeManager $themeManager, string $identifier, bool $force): int
    {
        $themeManager->clearCache();
        $theme = $themeManager->discover(fresh: true)->get($identifier);

        if (! $theme) {
            $this->error("Theme '{$identifier}' not found.");
            $this->line('Run <comment>php artisan cache:clear</comment> and try again.');

            return self::FAILURE;
        }

        $destinationPath = $theme->isProjectTheme()
            ? public_path("themes/{$identifier}")
            : public_path("vendor/mksine/themes/{$identifier}");

        if (! $force && is_dir($destinationPath)) {
            if (! $this->confirm("Assets for '{$theme->name}' already exist. Overwrite?")) {
                $this->info('Skipped.');

                return self::SUCCESS;
            }
        }

        $result = $themeManager->publishAssets($identifier);

        if ($result) {
            $this->info("Published assets for theme '{$theme->name}'");
            $this->line("  <comment>→</comment> {$destinationPath}");
            if ($theme->isPackageTheme()) {
                $this->newLine();
                $this->comment('Package themes publish to public/vendor/mksine/themes/');
                $this->comment('Project themes (in resources/views/themes/) publish to public/themes/');
            }
            $this->newLine();
            $this->line('Contents:');
            $this->listPublishedContents($destinationPath);

            return self::SUCCESS;
        }

        $this->error("Failed to publish assets for theme '{$theme->name}'. Make sure dist/ directory exists.");
        $this->line("  Expected: {$theme->path}/dist/");

        return self::FAILURE;
    }

    /**
     * Publish all themes' assets.
     */
    protected function publishAllThemes(ThemeManager $themeManager, bool $force): int
    {
        $themes = $themeManager->discover(fresh: true);

        if ($themes->isEmpty()) {
            $this->warn('No themes found.');

            return self::SUCCESS;
        }

        $this->info("Found {$themes->count()} theme(s).");
        $this->newLine();

        $published = 0;
        $failed = 0;

        foreach ($themes as $theme) {
            $result = $themeManager->publishAssets($theme->identifier);

            if ($result) {
                $this->line("  <info>✓</info> {$theme->name}");
                $published++;
            } else {
                $this->line("  <comment>⏭</comment> {$theme->name} (no dist/ found)");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Published: {$published}, Skipped: {$failed}");

        return self::SUCCESS;
    }

    /**
     * List published directory contents for user verification.
     */
    protected function listPublishedContents(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($path) + 1);
            $files[] = $relative;
        }

        sort($files);
        foreach (array_slice($files, 0, 15) as $file) {
            $this->line("    {$file}");
        }
        if (count($files) > 15) {
            $this->line('    ... and ' . (count($files) - 15) . ' more');
        }
    }
}
