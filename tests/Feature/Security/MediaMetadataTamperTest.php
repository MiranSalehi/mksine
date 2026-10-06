<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Livewire;
use Miran\Mksine\Filament\Resources\Media\Pages\EditMedia;
use Miran\Mksine\Filament\Resources\Media\Schemas\MediaForm;
use Miran\Mksine\Models\Media;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

const MEDIA_METADATA_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

/**
 * Filament schemas must belong to a Livewire component.
 */
final class MediaFormSchemaHarness extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public function render(): string
    {
        return '<div></div>';
    }
}

function mediaEditorUser(): User
{
    $user = User::factory()->create();

    foreach (['ViewAny:Media', 'View:Media', 'Update:Media'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

function storedPngMedia(array $attributes = []): Media
{
    $disk = (string) config('mksine.media.disk', 'public');
    $path = 'media/2026/09/21/ztest-media.png';

    Storage::fake($disk);
    Storage::disk($disk)->put($path, base64_decode(MEDIA_METADATA_PNG));

    return Media::create(array_merge([
        'name' => 'ztest-media',
        'file_name' => 'ztest-media.png',
        'mime_type' => 'image/png',
        'path' => $path,
        'disk' => $disk,
        'size' => Storage::disk($disk)->size($path),
        'width' => 1,
        'height' => 1,
        'url' => Storage::disk($disk)->url($path),
    ], $attributes));
}

it('does not dehydrate file metadata fields on the media form', function (string $field): void {
    $schema = MediaForm::configure(
        Schema::make(new MediaFormSchemaHarness)
            ->model(Media::class)
            ->operation('edit'),
    );

    $component = null;

    foreach ($schema->getFlatComponents(withHidden: true) as $candidate) {
        if ($candidate instanceof TextInput && $candidate->getName() === $field) {
            $component = $candidate;
            break;
        }
    }

    expect($component)->toBeInstanceOf(TextInput::class)
        ->and($component->isDisabled())->toBeTrue()
        ->and($component->isDehydrated())->toBeFalse()
        ->and($component->isSaved())->toBeFalse();
})->with([
    'file_name',
    'mime_type',
    'size',
    'width',
    'height',
    'path',
    'url',
]);

it('ignores a client-written mime type when saving a media record', function (): void {
    $media = storedPngMedia();

    Livewire::actingAs(mediaEditorUser())
        ->test(EditMedia::class, ['record' => $media->getRouteKey()])
        ->fillForm([
            'name' => 'still-a-png',
            'mime_type' => 'image/svg+xml',
            'file_name' => 'payload.svg',
            'path' => 'media/stolen.svg',
            'url' => 'https://evil.test/x',
            'size' => 1,
            'width' => 99,
            'height' => 99,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $media->refresh();

    expect($media->name)->toBe('still-a-png')
        ->and($media->mime_type)->toStartWith('image/png')
        ->and($media->file_name)->toBe('ztest-media.png')
        ->and($media->path)->toBe('media/2026/09/21/ztest-media.png')
        ->and($media->url)->not->toContain('evil.test')
        ->and($media->size)->toBeGreaterThan(1)
        ->and($media->width)->toBe(1)
        ->and($media->height)->toBe(1);
});

it('repairs a previously spoofed mime type from the file on disk', function (): void {
    $media = storedPngMedia([
        'mime_type' => 'image/svg+xml',
        'width' => 99,
        'height' => 99,
    ]);

    Livewire::actingAs(mediaEditorUser())
        ->test(EditMedia::class, ['record' => $media->getRouteKey()])
        ->fillForm(['name' => 'repaired'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($media->refresh()->mime_type)->toStartWith('image/png')
        ->and($media->width)->toBe(1)
        ->and($media->height)->toBe(1);
});
