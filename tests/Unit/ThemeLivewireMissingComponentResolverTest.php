<?php

declare(strict_types=1);

use Miran\Mksine\Core\Theme\ThemeLivewireMissingComponentResolver;
use Themes\FixtureTheme\Livewire\ProfileOrders;

test('returns null when name does not start with themes.', function () {
    expect(ThemeLivewireMissingComponentResolver::resolve('app.livewire.foo'))->toBeNull();
});

test('returns null when class does not exist', function () {
    expect(ThemeLivewireMissingComponentResolver::resolve('themes.none.exist.here'))->toBeNull();
});

test('returns null when class exists but is not a Livewire component', function () {
    expect(ThemeLivewireMissingComponentResolver::resolve('themes.illuminate.support.arr'))->toBeNull();
});

test('returns theme Livewire FQCN for dotted themes.* name', function () {
    $class = ThemeLivewireMissingComponentResolver::resolve('themes.fixture-theme.livewire.profile-orders');

    expect($class)->toBe(ProfileOrders::class);
});
