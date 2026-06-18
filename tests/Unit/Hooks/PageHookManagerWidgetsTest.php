<?php

declare(strict_types=1);

use Miran\Mksine\Core\Hooks\PageHookManager;
use Miran\Mksine\Filament\Pages\MksineDashboard;

test('page widget hooks merge widget classes in registration order', function (): void {
    $manager = new PageHookManager;

    $manager->extendWidgets(MksineDashboard::HOOK_NAME, function (array $widgets): array {
        $widgets[] = 'Widget\\One';

        return $widgets;
    });

    $manager->extendWidgets(MksineDashboard::HOOK_NAME, function (array $widgets): array {
        $widgets[] = 'Widget\\Two';

        return $widgets;
    });

    expect($manager->applyWidgets(MksineDashboard::HOOK_NAME, ['Widget\\Zero']))
        ->toBe(['Widget\\Zero', 'Widget\\One', 'Widget\\Two']);
});

test('dashboard hook name is stable for plugins', function (): void {
    expect(MksineDashboard::HOOK_NAME)->toBe('dashboard');
});
