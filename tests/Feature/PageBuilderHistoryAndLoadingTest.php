<?php

declare(strict_types=1);

use Livewire\Livewire;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;

it('keeps undo history off the Livewire snapshot and still undoes addBlock', function (): void {
    $stack = new ReflectionProperty(PageBuilder::class, 'historyStack');
    $position = new ReflectionProperty(PageBuilder::class, 'historyPosition');

    expect($stack->isPublic())->toBeFalse()
        ->and($position->isPublic())->toBeFalse();

    $test = Livewire::test(PageBuilder::class, ['value' => []])
        ->call('addBlock', 'heading');

    $snapshotData = $test->snapshot['data'] ?? [];
    expect($snapshotData)->not->toHaveKey('historyStack')
        ->and($snapshotData)->not->toHaveKey('historyPosition');

    expect($test->get('blocks'))->toHaveCount(1)
        ->and($test->instance()->canUndo())->toBeTrue();

    $sessionKey = 'mksine.page-builder.history.'.$test->id();
    expect(session($sessionKey))->toBeArray()
        ->and(session($sessionKey)['stack'] ?? [])->not->toBeEmpty();

    $test->call('undo');

    expect($test->get('blocks'))->toBe([])
        ->and($test->instance()->canUndo())->toBeFalse();
});

it('marks editor save with stable wire:loading and drops live entangle on the field', function (): void {
    $editor = file_get_contents(base_path('packages/mksine/resources/views/page-builder/component-editor.blade.php'));
    $field = file_get_contents(base_path('packages/mksine/resources/views/filament/forms/page-builder-field.blade.php'));
    $sortable = file_get_contents(base_path('packages/mksine/resources/js/page-builder-sortable.js'));
    $canvas = file_get_contents(base_path('packages/mksine/resources/views/page-builder/page-builder.blade.php'));
    $modals = file_get_contents(base_path('packages/mksine/resources/views/page-builder/components/modals.blade.php'));

    expect($editor)->not->toBeFalse()
        ->and($editor)->toContain('wire:loading.attr="disabled"')
        ->and($editor)->toContain('wire:target="save"')
        ->and($editor)->toContain('whitespace-nowrap')
        ->and($editor)->toContain('inline-flex');

    expect($field)->not->toBeFalse()
        ->and($field)->not->toContain('.live')
        ->and($field)->toContain('$wire.set')
        ->and($field)->toContain('150');

    expect($sortable)->not->toBeFalse()
        ->and($sortable)->toContain('sortableDomSignature')
        ->and($sortable)->toContain('sortablesAreLive');

    expect($canvas)->not->toBeFalse()
        ->and($canvas)->toContain('wire:loading.flex')
        ->and($canvas)->toContain('mksine-pb-canvas');

    expect($modals)->not->toBeFalse()
        ->and($modals)->toContain('$addBlockTarget')
        ->and($modals)->toContain('wire:target="{{ $addBlockTarget }}"')
        ->and($modals)->not->toContain('wire:target="addBlock"');
});
