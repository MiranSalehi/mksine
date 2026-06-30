<?php

declare(strict_types=1);

use Miran\Mksine\Support\LivewireUploadConfiguration;

test('extract max kb treats null rules as livewire default', function (): void {
    expect(LivewireUploadConfiguration::extractMaxKbFromRules(null))->toBe(12288);
});

test('zip accepted mime types include windows octet stream', function (): void {
    expect(LivewireUploadConfiguration::zipAcceptedMimeTypes())
        ->toContain('application/zip', 'application/x-zip-compressed', 'application/octet-stream');
});

test('zip filament rules use extension based mimes validation', function (): void {
    expect(LivewireUploadConfiguration::zipFilamentRules(102400))
        ->toBe(['file', 'mimes:zip', 'max:102400']);
});
