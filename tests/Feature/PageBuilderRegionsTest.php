<?php

declare(strict_types=1);

use Livewire\Livewire;
use Miran\Mksine\Core\PageBuilder\BlockRegion;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\Components\MksineFinanceShowcaseComponent;
use Miran\Mksine\Core\PageBuilder\Livewire\ComponentEditor;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;

/**
 * @param  array<int, mixed>  $components
 * @return list<string>
 */
function pageBuilderSchemaNames(array $components): array
{
    $names = [];

    foreach ($components as $component) {
        if (! is_object($component)) {
            continue;
        }

        if (method_exists($component, 'getName') && is_string($component->getName())) {
            $names[] = $component->getName();
        }

        if (! method_exists($component, 'getDefaultChildComponents')) {
            continue;
        }

        $children = $component->getDefaultChildComponents();
        if ($children instanceof \Filament\Schemas\Schema) {
            $children = $children->getComponents();
        }
        if (is_array($children)) {
            $names = array_merge($names, pageBuilderSchemaNames($children));
        }
    }

    return $names;
}

it('maps every registered block to fields that exist on its schema', function (): void {
    $registry = app(ComponentRegistry::class);
    $map = BlockRegion::map();

    expect(array_keys($map))->not->toBeEmpty();

    foreach ($registry->all() as $type => $class) {
        $regions = BlockRegion::forType($type);
        expect($regions)->toBe($class::regions());
        if ($type !== 'heading') {
            expect($regions)->not->toBeEmpty();
        }
        $names = pageBuilderSchemaNames($registry->getSchema($type));

        foreach ($regions as $definition) {
            foreach ($definition['fields'] ?? [] as $field) {
                expect($names)->toContain($field);
            }

            if (isset($definition['repeater'])) {
                expect($names)->toContain($definition['repeater']);
            }
        }
    }

    expect($map['heading'])->toBe([]);
});

it('marks finance regions only while the canvas is previewing', function (): void {
    $data = MksineFinanceShowcaseComponent::getDefaultData();

    $public = view('mksine::page-builder.render.mksine_finance_showcase', [
        'data' => $data,
    ])->render();

    $preview = view('mksine::page-builder.render.mksine_finance_showcase', [
        'data' => $data,
        'preview' => true,
    ])->render();

    expect($public)->not->toContain('data-mksine-region')
        ->and($preview)->toContain('data-mksine-region="heading"')
        ->and($preview)->toContain('data-mksine-region="cta_primary"')
        ->and($preview)->toContain('data-mksine-region-index="0"');
});

it('keeps the heading block free of a region so inline editing still owns the click', function (): void {
    $preview = view('mksine::page-builder.render.heading', [
        'data' => ['text' => 'Title', 'level' => 'h2', 'alignment' => 'left'],
        'preview' => true,
    ])->render();

    expect($preview)->toContain('data-mksine-inline="heading"')
        ->and($preview)->not->toContain('data-mksine-region');
});

it('merges a heading region save into the finance block and leaves the paragraphs', function (): void {
    $page = Livewire::test(PageBuilder::class, ['value' => [[
        'id' => 'fin',
        'type' => 'mksine_finance_showcase',
        'data' => [
            'heading_prefix' => 'Run your clinic finances ',
            'heading_accent' => 'without the chaos.',
            'heading_suffix' => '',
            'p1_before' => 'Invoices, payouts, and taxes in one ',
            'p1_highlight' => 'clear dashboard',
            'p1_after' => ' — built for busy teams.',
            'p2_before' => 'Automate reminders and get paid faster with ',
            'p2_highlight' => 'smart follow-ups',
            'p2_after' => ' your patients actually notice.',
            'social_caption' => 'Trusted by modern practices worldwide',
            'social_count' => '12,400+ teams',
            'trust_line' => 'No credit card required to explore the demo.',
            'cta_primary_label' => 'Start free trial',
            'cta_primary_url' => '#',
            'cta_secondary_label' => 'Talk to sales',
            'cta_secondary_url' => '/pricing',
            'mock_chart_caption' => 'Monthly revenue',
            'avatars' => [
                ['letter' => 'A'],
            ],
        ],
        'children' => null,
    ]]])
        ->call('editBlock', 'fin', null, null, 'heading')
        ->call('handleSaveBlockData', 'fin', [
            'heading' => 'Run your clinic finances without the chaos.',
            'heading_accent' => 'without the chaos.',
        ]);

    $data = $page->get('blocks')[0]['data'];

    expect($data['heading'])->toBe('Run your clinic finances without the chaos.')
        ->and($data['heading_accent'])->toBe('without the chaos.')
        ->and($data['paragraph_1'])->toContain('clear dashboard')
        ->and($data['paragraph_1_highlight'])->toBe('clear dashboard')
        ->and($data)->not->toHaveKey('heading_prefix');
});

it('does not invent a repeater row when the region index is missing', function (): void {
    $page = Livewire::test(PageBuilder::class, ['value' => [[
        'id' => 'fin',
        'type' => 'mksine_finance_showcase',
        'data' => MksineFinanceShowcaseComponent::getDefaultData(),
        'children' => null,
    ]]])
        ->call('editBlock', 'fin', null, null, 'avatars', 99)
        ->call('handleSaveBlockData', 'fin', ['letter' => 'Z']);

    expect($page->get('blocks')[0]['data']['avatars'])->toBe([
        ['letter' => 'A'],
        ['letter' => 'B'],
        ['letter' => 'C'],
        ['letter' => 'D'],
        ['letter' => 'E'],
    ]);
});

it('opens only the clicked finance region in the editor', function (): void {
    $data = MksineFinanceShowcaseComponent::getDefaultData();

    $heading = Livewire::test(ComponentEditor::class, [
        'blockId' => 'fin',
        'blockType' => 'mksine_finance_showcase',
        'blockData' => $data,
        'region' => 'heading',
    ]);

    expect($heading->html())->toContain('wire:loading.flex')
        ->and($heading->html())->toContain('wire:target="save"')
        ->and($heading->html())->not->toContain('min-w-[9.5rem]')
        ->and($heading->html())->toContain('Colored phrase')
        ->and($heading->html())->not->toContain('First paragraph')
        ->and($heading->html())->not->toContain('>Layout<');

    $avatar = Livewire::test(ComponentEditor::class, [
        'blockId' => 'fin',
        'blockType' => 'mksine_finance_showcase',
        'blockData' => $data,
        'region' => 'avatars',
        'regionIndex' => 1,
    ]);

    expect($avatar->get('blockData.letter'))->toBe('B')
        ->and($avatar->html())->toContain('Letter')
        ->and($avatar->html())->not->toContain('Heading');
});

it('posts the clicked region from the preview document', function (): void {
    $script = view('mksine::page-builder.preview.document', [
        'payload' => [[
            'id' => 'block_a',
            'type' => 'heading',
            'data' => ['text' => 'Title', 'level' => 'h2', 'alignment' => 'left'],
            'children' => null,
        ]],
    ])->render();

    expect($script)->toContain("region: region ? region.dataset.mksineRegion : ''")
        ->and($script)->toContain('data-mksine-insert="0"')
        ->and($script)->toContain('data-mksine-insert="1"')
        ->and($script)->toContain('parentId: place.parentId')
        ->and($script)->toContain('data-mksine-action="remove"')
        ->and($script)->toContain('data-mksine-drag')
        ->and($script)->toContain('data-mksine-drop')
        ->and($script)->toContain('movedId:')
        ->and($script)->toContain('mksine-drag-ghost')
        ->and($script)->toContain('mksine-drop-line')
        ->and($script)->toContain('if (state.active)')
        ->and($script)->toContain('window.__mksineSuppressClick = true')
        ->and($script)->toContain("closest('[data-mksine-drag]')")
        ->and($script)->toContain('background: rgb(245 243 255)')
        ->and($script)->toContain('outline: 1px solid rgb(228 228 231)')
        ->and($script)->toContain('[data-mksine-block-id]:hover:has([data-mksine-block-id]:hover)')
        ->and($script)->toContain('.mksine-insert-label { display: none; }')
        ->and($script)->toContain('is-selected')
        ->and($script)->toContain('function markSelected')
        ->and($script)->not->toContain('top: -14px')
        ->and($script)->not->toContain('padding-top: 32px')
        ->and($script)->toContain('data-mksine-action="settings"')
        ->and($script)->toContain('>Heading<')
        ->and(file_get_contents(base_path('packages/mksine/resources/views/page-builder/page-builder.blade.php')))
        ->toContain("component.call('editBlock', data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null, data.region || null, data.index === 0 || data.index ? data.index : null)")
        ->toContain('data.parentId || null')
        ->toContain("component.call('moveBlockAfterDrag'")
        ->toContain("data.type === 'move'");
});
