<?php

declare(strict_types=1);

it('puts the active toggle in tag information and seo in the side column', function () {
    $source = file_get_contents(dirname(__DIR__, 3).'/src/Filament/Resources/Tags/Schemas/TagForm.php');

    expect($source)->toContain("Toggle::make('is_active')")
        ->and($source)->toContain("CKEditor::make('description')")
        ->and($source)->not->toContain("Textarea::make('description')")
        ->and($source)->toContain("->key('tag_information')")
        ->and($source)->toContain("->key('seo')")
        ->and($source)->not->toContain("->key('settings')")
        ->and($source)->not->toContain('->collapsed()');
});
