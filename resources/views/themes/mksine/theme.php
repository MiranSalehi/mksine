<?php

declare(strict_types=1);

use Miran\Mksine\Core\Hooks\MenuLocationManager;
use Miran\Mksine\Core\PageBuilder\RegisterMksineThemeBlocks;

/**
 * Runs only while this theme is the active theme (see ThemeBootstrap).
 * Registers menu location keys used by this theme's Blade layouts,
 * plus the landing blocks that do not belong in the core picker.
 */
RegisterMksineThemeBlocks::register();

app(MenuLocationManager::class)->registerLocations([
    'header_primary' => __('mksine::menu_locations.theme_defaults.header_primary'),
    'footer_links' => __('mksine::menu_locations.theme_defaults.footer_links'),
]);
