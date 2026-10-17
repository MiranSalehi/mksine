<?php

declare(strict_types=1);

use Miran\Mksine\Filament\Resources\Pages\Pages\EditPage;
use Miran\Mksine\Filament\Resources\Pages\Schemas\PageForm;
use Miran\Mksine\Models\Page;

test('PageForm sets builder_content_width when switching to builder and only requires it for builder pages', function (): void {
    $path = dirname(__DIR__, 3).'/src/Filament/Resources/Pages/Schemas/PageForm.php';
    $content = file_get_contents($path);

    expect($content)->toBeString()
        ->and($content)->toContain('->afterStateHydrated(function (?string $state, callable $set, callable $get): void {')
        ->and($content)->toContain("->afterStateUpdated(function (?string \$state, callable \$set, callable \$get): void {")
        ->and($content)->toContain("\$set('builder_content_width', 'full')")
        ->and($content)->toContain('->selectablePlaceholder(false)')
        ->and($content)->toContain('static::showsBuilderSections($get, $record)')
        ->and($content)->toContain('static::resolvePageType($get, $record)')
        ->and($content)->not->toMatch('/builder_content_width[\s\S]{0,200}->required\(\),/');
});

test('PageForm resolvePageType falls back to the record before live form state hydrates', function (): void {
    $record = new Page(['type' => 'builder']);

    $type = PageForm::resolvePageType(fn (string $field): mixed => match ($field) {
        'type' => null,
        default => null,
    }, $record);

    expect($type)->toBe('builder');
});
