<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Filament\Resources\Pages\Pages\CreatePage;
use Miran\Mksine\Filament\Resources\Pages\Pages\EditPage;
use Miran\Mksine\Filament\Resources\Pages\Schemas\PageForm;
use Miran\Mksine\Models\Page;

uses(RefreshDatabase::class);

test('CreatePage defaults builder_content_width to full when type is builder', function (): void {
    $this->actingAs(User::factory()->create());

    $page = new class extends CreatePage
    {
        public function mutate(array $data): array
        {
            return $this->mutateFormDataBeforeCreate($data);
        }
    };

    $result = $page->mutate([
        'title' => 'Landing',
        'slug' => 'landing',
        'type' => 'builder',
        'status' => 'draft',
        'builder_content_width' => null,
    ]);

    expect($result['builder_content_width'])->toBe('full');
});

test('switching a new page to the builder seeds the canvas fields', function (): void {
    $state = [
        'builder_content_width' => null,
        'builder_payload' => null,
        'show_page_header' => null,
    ];

    $get = function (string $key) use (&$state): mixed {
        return $state[$key] ?? null;
    };
    $set = function (string $key, mixed $value) use (&$state): void {
        $state[$key] = $value;
    };

    PageForm::seedBuilderFields('builder', $set, $get);

    expect($state['builder_payload'])->toBe([])
        ->and($state['show_page_header'])->toBeFalse()
        ->and($state['builder_content_width'])->toBe('full');

    $state['show_page_header'] = true;
    $state['builder_payload'] = [['id' => 'block_a']];
    PageForm::seedBuilderFields('builder', $set, $get);

    expect($state['show_page_header'])->toBeTrue()
        ->and($state['builder_payload'])->toBe([['id' => 'block_a']]);
});

test('builder state keeps a false page header on the component', function (): void {
    $livewire = new stdClass;
    $livewire->data = [
        'type' => 'builder',
        'builder_payload' => [],
    ];

    PageForm::ensureBuilderStatePresent($livewire);

    expect($livewire->data)->toHaveKey('show_page_header')
        ->and($livewire->data['show_page_header'])->toBeFalse();

    $simple = new stdClass;
    $simple->data = ['type' => 'simple'];
    PageForm::ensureBuilderStatePresent($simple);

    expect($simple->data)->not->toHaveKey('show_page_header');
});

test('CreatePage leaves builder_content_width unchanged for simple pages', function (): void {
    $this->actingAs(User::factory()->create());

    $page = new class extends CreatePage
    {
        public function mutate(array $data): array
        {
            return $this->mutateFormDataBeforeCreate($data);
        }
    };

    $result = $page->mutate([
        'title' => 'About',
        'slug' => 'about',
        'type' => 'simple',
        'status' => 'draft',
        'content' => 'Body',
        'builder_content_width' => null,
    ]);

    expect($result['builder_content_width'] ?? null)->toBeNull();
});

test('PageForm shows builder sections for builder records even when type state is empty', function (): void {
    config(['mksine.features.page_builder' => true]);

    $record = new Page(['type' => 'builder']);

    $visible = PageForm::showsBuilderSections(fn (string $field): mixed => match ($field) {
        'type' => null,
        default => null,
    }, $record);

    expect($visible)->toBeTrue();
});

test('PageForm hides builder sections for simple records when type state is empty', function (): void {
    config(['mksine.features.page_builder' => true]);

    $record = new Page(['type' => 'simple']);

    $visible = PageForm::showsBuilderSections(fn (string $field): mixed => null, $record);

    expect($visible)->toBeFalse();
});

test('EditPage defaults builder_content_width to full when loading a builder page', function (): void {
    $this->actingAs(User::factory()->create());

    $record = Page::factory()->create([
        'type' => 'builder',
    ]);

    $page = new class extends EditPage
    {
        public function fillFormData(array $data): array
        {
            return $this->mutateFormDataBeforeFill($data);
        }
    };
    $page->record = $record;

    $result = $page->fillFormData([
        'title' => 'Landing',
        'slug' => 'landing',
        'type' => 'builder',
        'status' => 'draft',
        'builder_content_width' => null,
    ]);

    expect($result['builder_content_width'])->toBe('full');
});

test('seo section sits full width below the page sections', function (): void {
    $components = PageForm::configure(Schema::make())->getComponents(withHidden: true);
    $keys = array_map(
        fn ($component): ?string => $component->getKey(),
        $components,
    );
    $seo = $components[array_search('seo', $keys, true)];

    expect($keys)->toBe([
        'page_information',
        'content',
        'page_builder',
        'builder_display',
        'seo',
    ])->and($seo->getColumnSpan()['default'] ?? null)->toBe('full');
});
