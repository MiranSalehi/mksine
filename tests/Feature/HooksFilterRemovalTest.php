<?php

declare(strict_types=1);

use Miran\Mksine\Core\Hooks\HookFilterRegistry;
use Miran\Mksine\Core\Hooks\Hooks;

beforeEach(function (): void {
    $this->app->singleton(HookFilterRegistry::class, fn () => new HookFilterRegistry);
});

test('the facade adds, reports and removes named filters', function (): void {
    expect(Hooks::hasFilter('t'))->toBeFalse();

    Hooks::addFilter('t', fn (string $v): string => $v . 'old', 10, 'x');
    Hooks::addFilter('t', fn (string $v): string => $v . 'new', 10, 'x');

    expect(Hooks::hasFilter('t'))->toBeTrue()
        ->and(Hooks::filter('t', '0'))->toBe('0new');

    Hooks::removeFilter('t', 'x');

    expect(Hooks::hasFilter('t'))->toBeFalse()
        ->and(Hooks::filter('t', '0'))->toBe('0');
});

test('the facade blocks an id removed before a later add', function (): void {
    Hooks::removeFilter('t', 'x');
    Hooks::addFilter('t', fn (string $v): string => $v . 'a', 10, 'x');

    expect(Hooks::hasFilter('t'))->toBeFalse();
});
