<?php

namespace Miran\Mksine\Core\PageBuilder\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;

class ComponentEditor extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?string $blockId = null;

    public ?string $blockType = null;

    public ?array $blockData = [];

    public ?string $parentId = null;

    public ?int $columnIndex = null;

    protected $listeners = [
        'editBlock' => 'loadBlock',
    ];

    public function mount(?string $blockId = null, ?string $blockType = null, ?array $blockData = [], ?string $parentId = null, ?int $columnIndex = null): void
    {
        if ($blockId && $blockType !== null) {
            $this->loadBlock($blockId, $blockType, $blockData ?: [], $parentId, $columnIndex);
        }
    }

    public function loadBlock(string $blockId, string $blockType, array $blockData, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->blockId = $blockId;
        $this->blockType = $blockType;
        $this->blockData = $blockData;
        $this->parentId = $parentId;
        $this->columnIndex = $columnIndex;

        $this->form->fill($blockData);
    }

    public function form(Schema $form): Schema
    {
        if (! $this->blockType) {
            return $form->schema([]);
        }

        $registry = app(ComponentRegistry::class);
        $schema = $registry->getSchema($this->blockType);

        return $form
            ->schema($schema)
            ->statePath('blockData');
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

        $this->dispatch(
            'saveBlockData',
            blockId: $this->blockId,
            data: $data,
            parentId: $this->parentId,
            columnIndex: $this->columnIndex,
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
