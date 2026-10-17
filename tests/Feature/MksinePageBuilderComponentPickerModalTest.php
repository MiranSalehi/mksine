<?php

declare(strict_types=1);

use Livewire\Livewire;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;

it('page builder uses a tabbed modal for adding components instead of a sidebar partial', function (): void {
    $root = base_path('packages/mksine/resources/views/page-builder/page-builder.blade.php');
    $modals = base_path('packages/mksine/resources/views/page-builder/components/modals.blade.php');
    $sidebar = base_path('packages/mksine/resources/views/page-builder/components/sidebar.blade.php');

    expect(file_exists($root))->toBeTrue()
        ->and(file_exists($modals))->toBeTrue()
        ->and(file_exists($sidebar))->toBeFalse();

    $rootContent = file_get_contents($root);
    $modalsContent = file_get_contents($modals);
    expect($rootContent)->not->toBeFalse()
        ->and($modalsContent)->not->toBeFalse();

    expect($rootContent)->not->toContain('page-builder.components.sidebar')
        ->and($modalsContent)->toContain('component-picker-modal')
        ->and($modalsContent)->toContain('role="tablist"')
        ->and($modalsContent)->toContain('component-picker-panel-')
        ->and($modalsContent)->toContain('mksine-component-picker-panel')
        ->and($modalsContent)->toContain('setComponentPickerTab')
        ->and($modalsContent)->toContain('wire:loading.delay.short.flex')
        ->and($modalsContent)->toContain('wire:loading.delay.short.remove')
        ->and($modalsContent)->toContain('wire:loading.attr="disabled"')
        ->and($modalsContent)->toContain('wire:target="{{ $addBlockTarget }}"')
        ->and($modalsContent)->not->toContain('wire:target="addBlock"');

    expect($rootContent)->toContain('mksine-component-picker-panel-in');
});

it('page builder toolbar shows loading indicators only while requests run', function (): void {
    $toolbar = file_get_contents(base_path('packages/mksine/resources/views/page-builder/components/toolbar.blade.php'));

    expect($toolbar)->not->toBeFalse()
        ->not->toContain('min-w-[8.75rem]')
        ->not->toContain('min-w-[9.75rem]')
        ->not->toContain('wire:loading.class.remove="opacity-0"')
        ->toContain('wire:loading.flex')
        ->toContain('wire:target="openComponentPanel"')
        ->toContain('wire:target="toggleTemplatePanel,loadTemplate"');
});

it('can switch component picker tab without losing category data', function (): void {
    $byCategory = app(\Miran\Mksine\Core\PageBuilder\ComponentRegistry::class)->getByCategory()->toArray();
    expect($byCategory)->not->toBe([]);

    $test = Livewire::test(PageBuilder::class, ['value' => []])->call('openComponentPanel');
    $test->assertSet('showComponentPanel', true);

    $current = $test->get('componentPickerTab');
    expect($byCategory)->toHaveKey($current);

    $other = collect(array_keys($byCategory))->first(fn (string $k): bool => $k !== $current);
    expect($other)->not->toBeNull();

    $test->call('setComponentPickerTab', $other)->assertSet('componentPickerTab', $other);
});

it('filters the component library in the browser and still inserts through addBlock', function (): void {
    $modals = file_get_contents(base_path('packages/mksine/resources/views/page-builder/components/modals.blade.php'));

    expect($modals)->not->toBeFalse()
        ->toContain('type="search"')
        ->toContain('x-model="q"')
        ->toContain('@keydown.enter.prevent')
        ->toContain('data-mksine-picker-card')
        ->toContain('addBlock(');

    $html = Livewire::test(PageBuilder::class, ['value' => []])
        ->call('openComponentPanel')
        ->html();

    expect($html)->toContain('id="mksine-component-search"')
        ->toContain('data-mksine-picker-card')
        ->toContain('addBlock(');
});
