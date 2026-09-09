<?php

declare(strict_types=1);

use Miran\Mksine\Filament\Pages\SystemUpdate;

it('does not register a core ZIP FileUpload', function (): void {
    $pageFile = (new ReflectionClass(SystemUpdate::class))->getFileName();
    expect($pageFile)->not->toBeFalse();
    expect(file_get_contents($pageFile))->not->toContain('FileUpload');

    $view = dirname($pageFile, 4).'/resources/views/filament/pages/system-update.blade.php';
    expect(is_file($view))->toBeTrue();

    $blade = file_get_contents($view);
    expect($blade)->not->toContain('type="file"');
    expect($blade)->toContain('composer update miran/mksine');
    expect($blade)->toContain('core_release_archive_note');
});
