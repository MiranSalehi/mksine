<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Miran\Mksine\Livewire\MediaPickerModal;
use Miran\Mksine\Models\Media;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

const SAFE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 8"><rect width="8" height="8" fill="#0af"/></svg>';
const SCRIPTED_SVG = '<svg xmlns="http://www.w3.org/2000/svg" onload="fetch(`/admin/users`)"><rect width="8" height="8"/></svg>';

function svgUploader(): User
{
    $user = User::factory()->create();

    foreach (['ViewAny:Media', 'Create:Media'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

function fakeSvg(string $contents, string $name = 'ztest.svg', ?string $mime = null): File
{
    $file = UploadedFile::fake()->createWithContent($name, $contents);

    return $mime === null ? $file : $file->mimeType($mime);
}

beforeEach(function (): void {
    Storage::fake(config('mksine.media.disk', 'public'));

    // The guard has to hold even where an operator has opted back into SVG uploads.
    config()->set('mksine.media.allowed_types', ['image/png', 'image/svg+xml']);
});

it('rejects a scripted svg through the media picker', function (): void {
    Livewire::actingAs(svgUploader())
        ->test(MediaPickerModal::class)
        ->set('uploadedFiles', [fakeSvg(SCRIPTED_SVG)])
        ->call('uploadFiles')
        ->assertHasErrors('uploadedFiles.0');

    expect(Media::count())->toBe(0);
});

it('accepts a clean svg through the media picker when the operator allows svg', function (): void {
    Livewire::actingAs(svgUploader())
        ->test(MediaPickerModal::class)
        ->set('uploadedFiles', [fakeSvg(SAFE_SVG)])
        ->call('uploadFiles')
        ->assertHasNoErrors();

    expect(Media::where('file_name', 'ztest.svg')->exists())->toBeTrue();
});

it('inspects anything landing on disk as .svg even when the detected mime is not svg', function (): void {
    Livewire::actingAs(svgUploader())
        ->test(MediaPickerModal::class)
        ->set('uploadedFiles', [fakeSvg(SCRIPTED_SVG, 'ztest.svg', 'image/png')])
        ->call('uploadFiles')
        ->assertHasErrors('uploadedFiles.0');

    expect(Media::count())->toBe(0);
});

it('rejects every svg once svg is off the allowlist', function (): void {
    config()->set('mksine.media.allowed_types', ['image/png', 'image/jpeg']);

    Livewire::actingAs(svgUploader())
        ->test(MediaPickerModal::class)
        ->set('uploadedFiles', [fakeSvg(SAFE_SVG)])
        ->call('uploadFiles')
        ->assertHasErrors('uploadedFiles.0');

    expect(Media::count())->toBe(0);
});
