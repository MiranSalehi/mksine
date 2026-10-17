<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Miran\Mksine\Core\PageBuilder\Components\SpacerComponent;

test('spacer component select opens downward inside block editor modals', function (): void {
    $schema = SpacerComponent::getSchema();

    expect($schema)->toHaveCount(1);

    $field = $schema[0];

    expect($field)->toBeInstanceOf(Select::class)
        ->and($field->getName())->toBe('size')
        ->and($field->getPosition())->toBe('bottom');
});

test('page builder properties panel lets select menus extend outside the scroller', function (): void {
    $html = file_get_contents(base_path('packages/mksine/resources/views/page-builder/page-builder.blade.php'));

    expect($html)
        ->toContain('.mksine-pb-properties')
        ->toContain('overflow: visible')
        ->toContain('.mksine-pb-properties .fi-dropdown-panel');
});
