<?php

declare(strict_types=1);

test('PageForm uses mksine.features.page_builder for type options and builder section visibility', function (): void {
    $path = dirname(__DIR__, 3).'/src/Filament/Resources/Pages/Schemas/PageForm.php';
    $content = file_get_contents($path);

    expect($content)->toBeString()
        ->and($content)->toContain("config('mksine.features.page_builder'")
        ->and($content)->toContain('PageBuilderField::make')
        ->and($content)->toContain('mksine::pages.builder_display')
        ->and($content)->toContain('show_page_header')
        ->and($content)->toContain('builder_content_width');
});
