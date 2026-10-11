<?php

namespace Miran\Mksine\Core\PageBuilder\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Attributes\On;
use Livewire\Component;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Schema as FilamentSchema;
use Miran\Mksine\Core\PageBuilder\BlockPresentation;
use Miran\Mksine\Core\PageBuilder\BlockRegion;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\Components\MksineFinanceShowcaseComponent;

class ComponentEditor extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?string $blockId = null;

    public ?string $blockType = null;

    public ?array $blockData = [];

    public ?string $parentId = null;

    public ?int $columnIndex = null;

    public ?string $region = null;

    public ?int $regionIndex = null;

    /**
     * @var array{anchor: string, spacing: string, visibility: string}
     */
    public array $presentation = [];

    public function mount(?string $blockId = null, ?string $blockType = null, ?array $blockData = [], ?string $parentId = null, ?int $columnIndex = null, array $presentation = [], ?string $region = null, ?int $regionIndex = null): void
    {
        $this->region = $region !== null && $region !== '' ? $region : null;
        $this->regionIndex = $regionIndex;

        if ($blockId && $blockType !== null) {
            $this->loadBlock($blockId, $blockType, $blockData ?: [], $parentId, $columnIndex, $presentation);
        }
    }

    #[On('editBlock')]
    public function loadBlock(string $blockId, string $blockType, array $blockData, ?string $parentId = null, ?int $columnIndex = null, array $presentation = []): void
    {
        if ($blockType === MksineFinanceShowcaseComponent::getType()) {
            $blockData = MksineFinanceShowcaseComponent::forEditor($blockData);
        }

        $definition = BlockRegion::forType($blockType)[$this->region ?? ''] ?? null;
        if ($definition === null) {
            $this->region = null;
            $this->regionIndex = null;
        } elseif (isset($definition['repeater']) && $this->regionIndex !== null) {
            $list = array_values(is_array($blockData[$definition['repeater']] ?? null) ? $blockData[$definition['repeater']] : []);
            $blockData = is_array($list[$this->regionIndex] ?? null) ? $list[$this->regionIndex] : [];
        } else {
            $this->regionIndex = null;
        }

        $this->blockId = $blockId;
        $this->blockType = $blockType;
        $this->blockData = $blockData;
        $this->parentId = $parentId;
        $this->columnIndex = $columnIndex;
        $this->presentation = BlockPresentation::normalize($presentation);

        $state = $blockData;
        if ($this->region === null) {
            $state['presentation'] = $this->presentation;
        }

        $this->form->fill($state);
    }

    public function form(Schema $form): Schema
    {
        if (! $this->blockType) {
            return $form->schema([]);
        }

        $registry = app(ComponentRegistry::class);
        $schema = $this->schemaForRegion($registry->getSchema($this->blockType));

        if ($this->region === null) {
            $schema[] = Section::make(__('mksine::page_builder.presentation_section'))
                ->schema(BlockPresentation::fields())
                ->columns(2)
                ->columnSpanFull();
        }

        return $form
            ->schema($schema)
            ->statePath('blockData');
    }

    /**
     * @param  array<int, mixed>  $schema
     * @return array<int, mixed>
     */
    private function schemaForRegion(array $schema): array
    {
        $definition = BlockRegion::forType((string) $this->blockType)[$this->region ?? ''] ?? null;
        if ($definition === null) {
            return $schema;
        }

        if (isset($definition['repeater']) && $this->regionIndex !== null) {
            $repeater = $this->findNamed($schema, $definition['repeater']);
            $children = $repeater instanceof SchemaComponent ? $this->childComponents($repeater) : [];

            return $children !== [] ? $children : $schema;
        }

        $names = $definition['fields'] ?? [];
        if (isset($definition['repeater'])) {
            $names[] = $definition['repeater'];
        }

        $filtered = $this->filterSchema($schema, $names);

        return $filtered !== [] ? $filtered : $schema;
    }

    /**
     * @param  array<int, mixed>  $components
     */
    private function findNamed(array $components, string $name): ?object
    {
        foreach ($components as $component) {
            if (! is_object($component)) {
                continue;
            }

            if (method_exists($component, 'getName') && $component->getName() === $name) {
                return $component;
            }

            $found = $this->findNamed($this->childComponents($component), $name);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $components
     * @param  list<string>  $names
     * @return array<int, mixed>
     */
    private function filterSchema(array $components, array $names): array
    {
        $kept = [];

        foreach ($components as $component) {
            if (! is_object($component)) {
                continue;
            }

            $name = method_exists($component, 'getName') ? $component->getName() : null;
            if (is_string($name) && in_array($name, $names, true)) {
                $kept[] = $component;

                continue;
            }

            $children = $this->childComponents($component);
            if ($children === [] || ! method_exists($component, 'schema')) {
                continue;
            }

            $filtered = $this->filterSchema($children, $names);
            if ($filtered !== []) {
                $kept[] = $component->schema($filtered);
            }
        }

        return $kept;
    }

    /**
     * @return array<int, object>
     */
    private function childComponents(object $component): array
    {
        if (! method_exists($component, 'getDefaultChildComponents')) {
            return [];
        }

        $children = $component->getDefaultChildComponents();
        if ($children instanceof FilamentSchema) {
            $children = $children->getComponents();
        }

        if (! is_array($children)) {
            return [];
        }

        return array_values(array_filter($children, 'is_object'));
    }

    public function save(): void
    {
        /*
         * Use snapshot state, not `getState()`. Validated dehydrated state runs `pruneStateToMatchKeys()`
         * against Laravel rules; components inside collapsible repeater rows are treated as concealed and
         * their rules (and validated keys) are omitted, which drops nullable fields such as span_lg on save.
         * Snapshot dehydrates from the full Livewire raw state instead; PageBuilder still runs ComponentRegistry::validateComponent().
         */
        $data = $this->form->getStateSnapshot();
        $presentation = BlockPresentation::normalize(
            is_array($data['presentation'] ?? null) ? $data['presentation'] : $this->presentation,
        );
        unset($data['presentation']);

        $this->dispatch(
            'saveBlockData',
            blockId: $this->blockId,
            data: $data,
            parentId: $this->parentId,
            columnIndex: $this->columnIndex,
            presentation: $presentation,
        );

        $this->reset();
    }

    public function cancel(): void
    {
        $this->reset();
        $this->dispatch('closeEditor');
    }

    public function getComponentNameProperty(): string
    {
        if (! $this->blockType) {
            return '';
        }

        $registry = app(ComponentRegistry::class);
        $class = $registry->get($this->blockType);

        return $class ? $class::getName() : $this->blockType;
    }

    public function render()
    {
        return view('mksine::page-builder.component-editor');
    }
}
