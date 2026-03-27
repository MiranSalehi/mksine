<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Theme\ThemeManager;

class ThemePublishLangCommand extends Command
{
    protected $signature = 'mks:theme-publish-lang
                            {theme? : Theme identifier to publish translations (publishes all if omitted)}';

    protected $description = 'Publish theme translations to project lang directory (always overwrites)';

    public function handle(ThemeManager $themeManager): int
    {
        if (! function_exists('lang_path')) {
            $this->error('lang_path() is not available.');

            return self::FAILURE;
        }

        $themeIdentifier = $this->argument('theme');

        if ($themeIdentifier) {
            return $this->publishOne($themeManager, $themeIdentifier);
        }

        return $this->publishAll($themeManager);
    }

    private function publishOne(ThemeManager $themeManager, string $identifier): int
    {
        $themeManager->clearCache();
        $theme = $themeManager->discover(fresh: true)->get($identifier);

        if (! $theme) {
            $this->error("Theme '{$identifier}' not found.");

            return self::FAILURE;
        }

        if ($themeManager->publishThemeTranslations($identifier)) {
            $this->info("Published translations for theme '{$theme->name}'");
            $this->line('  → ' . lang_path('vendor/theme-' . $identifier));

            return self::SUCCESS;
        }

        $this->warn("No lang directory found for theme '{$theme->name}'.");

        return self::SUCCESS;
    }

    private function publishAll(ThemeManager $themeManager): int
    {
        $themeManager->clearCache();
        $themes = $themeManager->discover(fresh: true);

        if ($themes->isEmpty()) {
            $this->warn('No themes found.');

            return self::SUCCESS;
        }

        $published = 0;
        $skipped = 0;

        foreach ($themes as $theme) {
            if ($themeManager->publishThemeTranslations($theme->identifier)) {
                $this->line("  <info>✓</info> {$theme->name}");
                $published++;
            } else {
                $this->line("  <comment>⏭</comment> {$theme->name} (no lang found)");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Published: {$published}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
