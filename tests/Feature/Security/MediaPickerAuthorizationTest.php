<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Miran\Mksine\Livewire\MediaPickerModal;
use Miran\Mksine\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function mediaPickerUser(array $permissions = []): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

/**
 * @return list<string>
 */
function listedMediaNames(Testable $component): array
{
    return $component->viewData('mediaItems')->getCollection()->pluck('name')->all();
}

beforeEach(function (): void {
    Storage::fake(config('mksine.media.disk', 'public'));

    $this->media = Media::create([
        'name' => 'ztest-secret',
        'file_name' => 'ztest-secret.png',
        'mime_type' => 'image/png',
        'path' => 'media/ztest-secret.png',
        'disk' => config('mksine.media.disk', 'public'),
        'size' => 123,
        'url' => '/storage/media/ztest-secret.png',
    ]);
});

it('forbids opening the picker for a user who cannot view media', function (): void {
    Livewire::actingAs(mediaPickerUser())
        ->test(MediaPickerModal::class)
        ->call('open', 'data.image')
        ->assertForbidden();
});

it('forbids uploading for a user who cannot create media', function (): void {
    Livewire::actingAs(mediaPickerUser(['ViewAny:Media']))
        ->test(MediaPickerModal::class)
        ->set('uploadedFiles', [UploadedFile::fake()->image('ztest.png')])
        ->call('uploadFiles')
        ->assertForbidden();

    expect(Media::where('file_name', 'ztest.png')->exists())->toBeFalse();
});

it('forbids confirming and toggling a selection for a user who cannot view media', function (string $method): void {
    Livewire::actingAs(mediaPickerUser())
        ->test(MediaPickerModal::class)
        ->call($method, 1)
        ->assertForbidden();
})->with(['confirm', 'toggleSelection']);

it('throws when a picker action is reached outside a Livewire request', function (): void {
    $this->actingAs(mediaPickerUser());

    (new MediaPickerModal)->open('data.image');
})->throws(AuthorizationException::class);

it('lists nothing when an unauthorised client forces the modal open', function (): void {
    $component = Livewire::actingAs(mediaPickerUser())
        ->test(MediaPickerModal::class)
        ->set('isOpen', true);

    expect(listedMediaNames($component))->toBe([]);
});

it('does not expose media details to an unauthorised client', function (): void {
    $component = Livewire::actingAs(mediaPickerUser())
        ->test(MediaPickerModal::class)
        ->set('detailMediaId', $this->media->id);

    expect($component->instance()->detailMedia)->toBeNull();
});

it('lets a permitted user open the picker', function (): void {
    Livewire::actingAs(mediaPickerUser(['ViewAny:Media']))
        ->test(MediaPickerModal::class)
        ->call('open', 'data.image')
        ->assertOk()
        ->assertSet('isOpen', true);
});

it('lists the library for a permitted user', function (): void {
    $component = Livewire::actingAs(mediaPickerUser(['ViewAny:Media']))
        ->test(MediaPickerModal::class)
        ->set('isOpen', true);

    expect(listedMediaNames($component))->toContain('ztest-secret');
});

it('lets a permitted user upload', function (): void {
    Livewire::actingAs(mediaPickerUser(['ViewAny:Media', 'Create:Media']))
        ->test(MediaPickerModal::class)
        ->call('open', 'data.image')
        ->set('uploadedFiles', [UploadedFile::fake()->image('ztest-upload.png')])
        ->call('uploadFiles')
        ->assertOk();

    expect(Media::where('file_name', 'ztest-upload.png')->exists())->toBeTrue();
});

it('lets a super admin through without explicit media permissions', function (): void {
    $role = config('filament-shield.super_admin.name', 'super_admin');
    Role::findOrCreate($role, 'web');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole($role);

    $component = Livewire::actingAs($superAdmin)
        ->test(MediaPickerModal::class)
        ->set('isOpen', true);

    expect(listedMediaNames($component))->toContain('ztest-secret');
});
