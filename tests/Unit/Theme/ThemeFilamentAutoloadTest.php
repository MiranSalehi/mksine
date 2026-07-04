<?php

declare(strict_types=1);

use Miran\Mksine\Core\Theme\ThemeBootstrap;
use Themes\SampleStore\Filament\Pages\Settings\SampleContactSettingsPage;

test('theme autoload registration exposes filament page classes before theme bootstrap boot', function (): void {
    $themePath = dirname(__DIR__, 2).'/Fixtures/Themes/sample-store';

    expect(class_exists(SampleContactSettingsPage::class))->toBeFalse();

    ThemeBootstrap::registerAutoloadForTheme('sample-store', $themePath);

    expect(class_exists(SampleContactSettingsPage::class))->toBeTrue()
        ->and(SampleContactSettingsPage::shouldRegisterNavigation())->toBeTrue();
});

test('ensure active theme autoload registered is safe when no theme is active', function (): void {
    ThemeBootstrap::ensureActiveThemeAutoloadRegistered();

    expect(true)->toBeTrue();
});
