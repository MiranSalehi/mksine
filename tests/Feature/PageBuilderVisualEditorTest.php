<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;
use Miran\Mksine\Core\PageBuilder\PageBuilderPreviewStore;
use Miran\Mksine\Core\PageBuilder\PageBuilderRevisionStore;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\PageBuilderRevision;
use Miran\Mksine\Models\PageBuilderSection;

uses(RefreshDatabase::class);

function visualHeading(string $id, string $text): array
{
    return [
        'id' => $id,
        'type' => 'heading',
        'data' => ['text' => $text, 'level' => 'h2', 'alignment' => 'left'],
        'children' => null,
    ];
}

it('uses real canvas labels and a fixed preview frame', function (): void {
    expect(__('mksine::page_builder.viewport_desktop'))->toBe('Desktop')
        ->and(__('mksine::page_builder.navigator'))->toBe('Navigator');

    $html = Livewire::test(PageBuilder::class, [
        'value' => [visualHeading('block_a', 'A')],
    ])->html();

    expect($html)->toContain('mksine-pb-stage')
        ->toContain('min-height: 640px')
        ->toContain('>Desktop<')
        ->not->toContain('mksine-pb-navigator')
        ->not->toContain('id="blocks-list"')
        ->not->toContain('mksine-pb-frame relative is-empty')
        ->toContain('__mksineRestorePreviewScroll')
        ->toContain('contentWindow.scrollTo(0, y)')
        ->not->toContain('mksine::page_builder.viewport_desktop');
});

it('hides the canvas preview from guests and renders the unsaved payload', function (): void {
    $user = User::factory()->create();
    $payload = [
        visualHeading('block_a', 'Canvas title'),
        [
            'id' => 'block_b',
            'type' => 'button',
            'data' => ['text' => 'Go', 'url' => 'javascript:alert(1)'],
            'children' => null,
        ],
    ];

    $this->actingAs($user);
    $token = app(PageBuilderPreviewStore::class)->put($user->id, $payload);
    $url = URL::temporarySignedRoute('mksine.page-builder.preview', now()->addMinutes(10), ['token' => $token]);

    auth()->logout();
    $this->get($url)->assertRedirect();

    $this->actingAs($user);
    $html = $this->get($url)->assertSuccessful()->getContent();

    expect($html)
        ->toContain('Canvas title')
        ->toContain('data-mksine-block-id="block_a"')
        ->toContain('href="#"')
        ->not->toContain('javascript:')
        ->not->toContain('site-header-bar');
});

it('keeps one preview document when the viewport changes and skips a reload when the payload is unchanged', function (): void {
    $this->actingAs(User::factory()->create());

    $test = Livewire::test(PageBuilder::class, [
        'value' => [visualHeading('block_a', 'A')],
    ]);

    $url = $test->get('previewUrl');

    expect($url)->toBeString()
        ->and($url)->not->toContain('viewport')
        ->and($url)->not->toContain('mobile');

    $html = $test->html();
    expect($html)->toContain("viewport === 'tablet'")
        ->toContain("viewport === 'mobile'")
        ->not->toContain('wire:click="setPreviewViewport')
        ->toContain("if (data.type === 'select') return;");

    $test->call('setPreviewViewport', 'mobile')
        ->call('syncPreview');

    expect($test->get('previewViewport'))->toBe('mobile')
        ->and($test->get('previewUrl'))->toBe($url);

    $test->call('addBlock', 'spacer');

    expect($test->get('previewUrl'))->not->toBe($url);
});

it('stores style tokens on presentation and leaves an old payload without extra classes', function (): void {
    $saved = Livewire::test(PageBuilder::class, [
        'value' => [visualHeading('block_a', 'A')],
    ])->call('editBlock', 'block_a')
        ->call('saveBlock', [
            'text' => 'A',
            'level' => 'h2',
            'alignment' => 'left',
            'presentation' => [
                'padding' => 'huge',
                'background' => 'red',
                'align' => 'sideways',
                'width' => 'narrow',
                'spacing' => 'sm',
            ],
        ])->get('blocks');

    expect($saved[0]['presentation']['padding'])->toBe('none')
        ->and($saved[0]['presentation']['background'])->toBe('none')
        ->and($saved[0]['presentation']['align'])->toBe('start')
        ->and($saved[0]['presentation']['width'])->toBe('full')
        ->and($saved[0]['presentation']['spacing'])->toBe('sm');

    $old = view('mksine::page-builder.render.block', [
        'block' => visualHeading('block_a', 'Title'),
    ])->render();

    expect($old)->toContain('<h2')
        ->and($old)->not->toContain('p-4')
        ->and($old)->not->toContain('bg-zinc-100')
        ->and($old)->not->toContain('max-w-4xl')
        ->and($old)->not->toContain('text-center');

    $styled = view('mksine::page-builder.render.block', [
        'block' => [
            ...visualHeading('block_a', 'Title'),
            'presentation' => [
                'padding' => 'md',
                'background' => 'brand',
                'align' => 'center',
                'width' => 'contained',
            ],
        ],
    ])->render();

    expect($styled)->toContain('padding: 1rem')
        ->toContain('background-color: #f5f3ff')
        ->toContain('text-align: center')
        ->toContain('max-w-4xl')
        ->toContain('<h2');

    $painted = view('mksine::page-builder.render.block', [
        'block' => [
            ...visualHeading('block_a', 'Title'),
            'presentation' => [
                'align' => 'right',
                'text_color' => '#112233',
                'background_color' => 'red',
                'border_width' => 'md',
                'border_color' => '#abcdef',
                'border_radius' => 'sm',
                'padding' => 'sm',
                'spacing' => 'lg',
            ],
        ],
    ])->render();

    expect($painted)->toContain('data-mksine-align="right"')
        ->toContain('text-align: right')
        ->toContain('color: #112233')
        ->toContain('data-mksine-text')
        ->not->toContain('background-color: red')
        ->toContain('border-width: 2px')
        ->toContain('border-color: #abcdef')
        ->toContain('border-radius: 0.375rem')
        ->toContain('padding: 0.5rem')
        ->toContain('margin-bottom: 2rem');

    $features = view('mksine::page-builder.render.block', [
        'block' => [
            'id' => 'block_features',
            'type' => 'features',
            'data' => [
                'features' => [
                    ['icon' => 'heroicon-o-star', 'title' => 'Design', 'description' => 'Interfaces'],
                ],
            ],
            'presentation' => ['align' => 'center'],
        ],
    ])->render();

    expect($features)->toContain('data-mksine-align="center"')
        ->toContain('w-12')
        ->and(file_get_contents(dirname(__DIR__, 2).'/resources/dist/blocks.css'))
        ->toContain('[data-mksine-align="center"] :is(.w-8, .w-9, .w-10, .w-11, .w-12');
});

it('keeps an omitted navigator block and selects the iframe target', function (): void {
    $test = Livewire::test(PageBuilder::class, [
        'value' => [visualHeading('block_a', 'A'), visualHeading('block_b', 'B')],
    ])->call('reorderNavigator', ['block_b']);

    $reordered = $test->get('blocks');

    expect($reordered)->toHaveCount(2)
        ->and($reordered[0]['id'])->toBe('block_b')
        ->and($reordered[1]['id'])->toBe('block_a');

    $afterUndo = $test->call('selectBlock', 'block_b')
        ->assertSet('selectedBlockId', 'block_b')
        ->call('addBlock', 'heading')
        ->call('undo')
        ->get('blocks');

    expect($afterUndo)->toHaveCount(2);
});

it('shares a saved section by reference and detaches it with one copy', function (): void {
    $section = PageBuilderSection::query()->create([
        'name' => 'Shared hero',
        'builder_payload' => [visualHeading('inner', 'Shared')],
    ]);

    $reference = [
        'id' => 'ref_1',
        'type' => 'section_ref',
        'data' => ['section_id' => $section->id],
        'children' => null,
    ];

    $first = Page::factory()->create([
        'type' => 'builder',
        'status' => 'draft',
        'slug' => 'section-page-a',
        'builder_payload' => [$reference],
    ]);
    $second = Page::factory()->create([
        'type' => 'builder',
        'status' => 'draft',
        'slug' => 'section-page-b',
        'builder_payload' => [$reference],
    ]);

    $render = function (Page $page): string {
        return view('mksine::page-builder.render.payload', [
            'payload' => $page->builder_payload,
        ])->render();
    };

    expect($render($first))->toContain('Shared')
        ->and($render($second))->toContain('Shared');

    $section->builder_payload = [visualHeading('inner', 'Changed')];
    $section->save();

    expect($render($first->fresh()))->toContain('Changed')
        ->and($render($second->fresh()))->toContain('Changed');

    $copy = $first->fresh()->duplicateAsDraft();
    expect($copy->builder_payload[0]['type'])->toBe('section_ref')
        ->and($copy->builder_payload[0]['data']['section_id'])->toBe($section->id);

    app(PageBuilderRevisionStore::class)->record($first->fresh());
    $revision = PageBuilderRevision::query()->where('page_id', $first->id)->first();
    expect($revision)->not->toBeNull()
        ->and($revision->builder_payload[0]['type'])->toBe('section_ref')
        ->and(json_encode($revision->builder_payload))->not->toContain('Changed');

    $section->builder_payload = [
        [
            'id' => 'loop',
            'type' => 'section_ref',
            'data' => ['section_id' => $section->id],
            'children' => null,
        ],
    ];
    $section->save();
    expect($section->fresh()->builder_payload)->toBe([]);

    PageBuilderSection::withoutEvents(function () use ($section): void {
        $section->builder_payload = [
            visualHeading('inner', 'Once'),
            [
                'id' => 'loop',
                'type' => 'section_ref',
                'data' => ['section_id' => $section->id],
                'children' => null,
            ],
        ];
        $section->save();
    });

    $cycled = $render($first->fresh());
    expect(substr_count($cycled, 'Once'))->toBe(1);

    $missing = view('mksine::page-builder.render.block', [
        'block' => [
            'id' => 'gone',
            'type' => 'section_ref',
            'data' => ['section_id' => 999999],
            'children' => null,
        ],
        'preview' => false,
    ])->render();
    $previewMissing = view('mksine::page-builder.render.block', [
        'block' => [
            'id' => 'gone',
            'type' => 'section_ref',
            'data' => ['section_id' => 999999],
            'children' => null,
        ],
        'preview' => true,
    ])->render();

    expect($missing)->not->toContain(__('mksine::page_builder.section_missing'))
        ->and($previewMissing)->toContain(__('mksine::page_builder.section_missing'));

    $section->builder_payload = [visualHeading('inner', 'Once')];
    $section->save();

    $unlinked = Livewire::test(PageBuilder::class, [
        'value' => [[
            'id' => 'ref_1',
            'type' => 'section_ref',
            'data' => ['section_id' => $section->id],
            'children' => null,
        ]],
    ])->call('unlinkSection', 'ref_1')->get('blocks');

    expect($unlinked)->toHaveCount(1)
        ->and($unlinked[0]['type'])->toBe('heading')
        ->and($unlinked[0]['id'])->not->toBe('inner')
        ->and($unlinked[0]['data']['text'])->toBe('Once');
});

it('writes a heading from the canvas as plain text and leaves rich text closed', function (): void {
    $saved = Livewire::test(PageBuilder::class, [
        'value' => [
            visualHeading('block_a', 'Before'),
            [
                'id' => 'text_a',
                'type' => 'text',
                'data' => ['content' => '<p>Body</p>'],
                'children' => null,
            ],
        ],
    ])->call('updateHeadingText', 'block_a', '<b>Hi</b>')
        ->call('updateHeadingText', 'text_a', '<b>Nope</b>')
        ->get('blocks');

    expect($saved[0]['data']['text'])->toBe('Hi')
        ->and($saved[0]['data']['text'])->not->toContain('<')
        ->and($saved[1]['data']['content'])->toBe('<p>Body</p>');

    $html = view('mksine::page-builder.preview.document', [
        'payload' => $saved,
    ])->render();

    expect(substr_count($html, 'contenteditable'))->toBe(1)
        ->and($html)->toContain('data-mksine-inline="heading"')
        ->and($html)->toContain('Hi');
});

it('fills an empty canvas with one insert target', function (): void {
    $html = view('mksine::page-builder.preview.document', [
        'payload' => [],
    ])->render();

    expect($html)->toContain('mksine-canvas-empty')
        ->toContain('data-mksine-insert="0"')
        ->toContain('Your page is empty')
        ->toContain('Add component here')
        ->not->toContain('min-height: 420px')
        ->not->toContain('mksine-canvas-empty-mark')
        ->not->toContain('Start building by adding your first component')
        ->not->toContain('class="mksine-insert');

    $builder = Livewire::test(PageBuilder::class, [
        'value' => [],
    ])->html();

    expect($builder)->toContain('mksine-pb-frame relative is-empty')
        ->toContain('height: auto')
        ->toContain('wire:click="addBlockAtPosition(0)"')
        ->toContain('wire:target="addBlockAtPosition"')
        ->toContain('wire:loading.flex')
        ->toContain('mksine-pb-empty');

    $bridge = file_get_contents(base_path('packages/mksine/resources/js/page-builder.js'));
    expect($bridge)->toContain('bindPreviewBridge')
        ->toContain("data.type === 'insert'");
});

it('opens block settings in a slideover and clears selection on close', function (): void {
    $editor = Livewire::test(PageBuilder::class, [
        'value' => [visualHeading('block_a', 'A')],
    ])->call('editBlock', 'block_a');

    expect($editor->get('editingBlockId'))->toBe('block_a')
        ->and($editor->get('selectedBlockId'))->toBe('block_a')
        ->and($editor->html())->toContain('id="block-editor-slideover"')
        ->and($editor->html())->toContain('fi-modal-slide-over')
        ->and($editor->html())->toContain('wire:click="save"')
        ->and($editor->html())->not->toContain('<aside class="mksine-pb-properties"')
        ->and($editor->html())->not->toContain('block-editor-modal');

    $editor->call('closeEditor');

    expect($editor->get('editingBlockId'))->toBeNull()
        ->and($editor->get('selectedBlockId'))->toBeNull()
        ->and($editor->html())->not->toContain('wire:click="save"');
});

it('treats fullscreen as a workspace and closes the properties panel before leaving it', function (): void {
    $builder = Livewire::test(PageBuilder::class, [
        'value' => [visualHeading('block_a', 'A')],
    ])->html();

    expect($builder)->toContain('is-workspace')
        ->and($builder)->toContain('if ($wire.showPasteModal)')
        ->and($builder)->toContain('if ($wire.editingBlockId)')
        ->and($builder)->toContain('$wire.closeEditor()')
        ->and($builder)->toContain('__mksineBuilderPageName')
        ->and($builder)->not->toContain('overflow-y-auto overscroll-contain')
        ->and($builder)->toContain('form.page_information.title')
        ->and($builder)->toContain('flex: 1 1 auto');
});
