<?php

namespace Miran\Mksine\Core\PageBuilder\Livewire;

use Livewire\Component;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\TemplateRegistry;

class PageBuilder extends Component
{
    /**
     * The builder blocks/items.
     */
    public array $blocks = [];

    /**
     * Currently editing block ID.
     */
    public ?string $editingBlockId = null;

    /**
     * Currently editing block data.
     */
    public array $editingBlockData = [];

    /**
     * Show add component panel.
     */
    public bool $showComponentPanel = false;

    /**
     * Show template selection panel.
     */
    public bool $showTemplatePanel = false;

    /**
     * Target position for new component.
     */
    public ?int $insertAtPosition = null;

    /**
     * Target column for nested insert.
     */
    public ?string $insertInColumn = null;

    /**
     * Parent block ID for nested insert.
     */
    public ?string $insertInParent = null;

    /**
     * History stack for undo/redo.
     */
    public array $historyStack = [];

    /**
     * Current position in history (for redo).
     */
    public int $historyPosition = -1;

    /**
     * Last block JSON for copy (read by frontend after getBlockJsonForCopy).
     */
    public ?string $copyBlockJson = null;

    /**
     * Preview form action URL (so frontend always has it after Livewire updates).
     */
    public string $previewUrl = '';

    /**
     * Maximum history size.
     */
    protected int $maxHistorySize = 50;

    protected $listeners = [
        'builder:reorder' => 'reorderBlocks',
        'builder:reorderColumn' => 'reorderColumnBlocks',
        'saveBlockData' => 'handleSaveBlockData',
        'closeEditor' => 'closeEditor',
    ];

    public function mount(array $value = []): void
    {
        $this->blocks = $value;
        $this->previewUrl = route('mksine.page-builder.preview');
        $this->saveHistory();
    }

    /**
     * Save current state to history.
     */
    protected function saveHistory(): void
    {
        // Remove any "redo" states if we're not at the end
        if ($this->historyPosition < count($this->historyStack) - 1) {
            $this->historyStack = array_slice($this->historyStack, 0, $this->historyPosition + 1);
        }

        // Add current state (deep clone)
        $this->historyStack[] = json_decode(json_encode($this->blocks), true);

        // Limit history size
        if (count($this->historyStack) > $this->maxHistorySize) {
            array_shift($this->historyStack);
            $this->historyPosition = count($this->historyStack) - 1;
        } else {
            $this->historyPosition++;
        }
    }

    /**
     * Undo last action.
     */
    public function undo(): void
    {
        if ($this->historyPosition <= 0) {
            return;
        }

        $this->historyPosition--;
        $this->blocks = json_decode(json_encode($this->historyStack[$this->historyPosition]), true);
        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Redo last undone action.
     */
    public function redo(): void
    {
        if ($this->historyPosition >= count($this->historyStack) - 1) {
            return;
        }

        $this->historyPosition++;
        $this->blocks = json_decode(json_encode($this->historyStack[$this->historyPosition]), true);
        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Check if undo is available.
     */
    public function canUndo(): bool
    {
        return $this->historyPosition > 0;
    }

    /**
     * Check if redo is available.
     */
    public function canRedo(): bool
    {
        return $this->historyPosition < count($this->historyStack) - 1;
    }

    public function getComponentsProperty(): array
    {
        return app(ComponentRegistry::class)->getByCategory()->toArray();
    }

    public function getCategoryMetaProperty(): array
    {
        return ComponentRegistry::getCategoryMeta();
    }

    /**
     * Add a new block.
     */
    public function addBlock(string $type, ?int $position = null, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->saveHistory();

        $registry = app(ComponentRegistry::class);
        $instance = $registry->createInstance($type);

        if (! $instance) {
            return;
        }

        if ($parentId !== null && $columnIndex !== null) {
            // Add to a column in a parent block
            $this->addBlockToColumn($instance, $parentId, $columnIndex, $position);
        } else {
            // Add to root level
            if ($position !== null) {
                array_splice($this->blocks, $position, 0, [$instance]);
            } else {
                $this->blocks[] = $instance;
            }
        }

        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Add block to a specific column.
     */
    protected function addBlockToColumn(array $instance, string $parentId, int $columnIndex, ?int $position = null): void
    {
        foreach ($this->blocks as &$block) {
            if ($block['id'] === $parentId && isset($block['children'][$columnIndex])) {
                if ($position !== null) {
                    array_splice($block['children'][$columnIndex]['items'], $position, 0, [$instance]);
                } else {
                    $block['children'][$columnIndex]['items'][] = $instance;
                }

                break;
            }
        }
    }

    /**
     * Remove a block.
     */
    public function removeBlock(string $blockId, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->saveHistory();

        if ($parentId !== null && $columnIndex !== null) {
            // Remove from column
            foreach ($this->blocks as &$block) {
                if ($block['id'] === $parentId && isset($block['children'][$columnIndex])) {
                    $block['children'][$columnIndex]['items'] = array_values(
                        array_filter($block['children'][$columnIndex]['items'], fn ($item) => $item['id'] !== $blockId)
                    );

                    break;
                }
            }
        } else {
            // Remove from root
            $this->blocks = array_values(
                array_filter($this->blocks, fn ($block) => $block['id'] !== $blockId)
            );
        }

        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Duplicate a block.
     */
    public function duplicateBlock(string $blockId, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->saveHistory();

        $block = $this->findBlock($blockId, $parentId, $columnIndex);

        if (! $block) {
            return;
        }

        // Create a deep copy with new IDs
        $duplicate = $this->deepCopyBlock($block);

        if ($parentId !== null && $columnIndex !== null) {
            // Add to column after original
            foreach ($this->blocks as &$parentBlock) {
                if ($parentBlock['id'] === $parentId && isset($parentBlock['children'][$columnIndex])) {
                    $items = &$parentBlock['children'][$columnIndex]['items'];
                    $index = array_search($blockId, array_column($items, 'id'));
                    if ($index !== false) {
                        array_splice($items, $index + 1, 0, [$duplicate]);
                    }

                    break;
                }
            }
        } else {
            // Add to root after original
            $index = array_search($blockId, array_column($this->blocks, 'id'));
            if ($index !== false) {
                array_splice($this->blocks, $index + 1, 0, [$duplicate]);
            }
        }

        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Deep copy a block with new IDs.
     */
    protected function deepCopyBlock(array $block): array
    {
        $copy = $block;
        $copy['id'] = uniqid('block_');

        if (isset($copy['children']) && is_array($copy['children'])) {
            foreach ($copy['children'] as &$column) {
                $column['id'] = uniqid('col_');
                if (isset($column['items'])) {
                    foreach ($column['items'] as &$item) {
                        $item = $this->deepCopyBlock($item);
                    }
                }
            }
        }

        return $copy;
    }

    /**
     * Copy block to clipboard (dispatches event for JS to write clipboard).
     */
    public function copyBlock(string $blockId, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $block = $this->findBlock($blockId, $parentId, $columnIndex);

        if (! $block) {
            return;
        }

        $this->dispatch('copy-to-clipboard', [
            'data' => json_encode($block),
            'message' => __('Component copied!'),
        ]);
    }

    /**
     * Set copyBlockJson so frontend can read it and copy to clipboard (no dispatch needed).
     */
    public function getBlockJsonForCopy(string $blockId, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $block = $this->findBlock($blockId, $parentId, $columnIndex);
        $this->copyBlockJson = $block ? json_encode($block) : null;
    }

    /**
     * Paste block from clipboard.
     */
    public function pasteBlock(string $clipboardData, ?int $position = null): void
    {
        $this->saveHistory();

        try {
            $block = json_decode($clipboardData, true);

            if (! is_array($block) || empty($block['type'])) {
                throw new \Exception('Invalid clipboard data');
            }

            $block['id'] = uniqid('block_');

            if (! empty($block['children'])) {
                $block['children'] = $this->regenerateColumnIds($block['children']);
            }

            if ($position !== null && $position >= 0) {
                array_splice($this->blocks, $position, 0, [$block]);
            } else {
                $this->blocks[] = $block;
            }

            $this->dispatch('paste-success', ['message' => __('Component pasted!')]);
            $this->dispatch('builder:updated');
            $this->emitValue();
        } catch (\Exception $e) {
            $this->dispatch('paste-error', ['message' => __('Invalid clipboard data')]);
        }
    }

    /**
     * Regenerate IDs for columns and nested items.
     */
    protected function regenerateColumnIds(array $children): array
    {
        $result = [];
        foreach ($children as $column) {
            $newCol = $column;
            $newCol['id'] = uniqid('col_');
            if (! empty($newCol['items'])) {
                $newCol['items'] = array_map(function ($item) {
                    $item['id'] = uniqid('block_');
                    if (! empty($item['children'])) {
                        $item['children'] = $this->regenerateColumnIds($item['children']);
                    }
                    return $item;
                }, $newCol['items']);
            }
            $result[] = $newCol;
        }
        return $result;
    }

    /**
     * Request paste from system clipboard. Dispatches to JS to read clipboard, then pasteBlock is called.
     * Pass null to paste at end, or int for specific position.
     */
    public function pasteFromClipboard(?int $position = null): void
    {
        $this->dispatch('request-paste', ['position' => $position]);
    }

    /**
     * Paste from clipboard at the end of the list (toolbar / Cmd+V).
     */
    public function pasteAtEnd(): void
    {
        $this->pasteFromClipboard(null);
    }

    /**
     * Find a block by ID.
     */
    protected function findBlock(string $blockId, ?string $parentId = null, ?int $columnIndex = null): ?array
    {
        if ($parentId !== null && $columnIndex !== null) {
            foreach ($this->blocks as $block) {
                if ($block['id'] === $parentId && isset($block['children'][$columnIndex])) {
                    foreach ($block['children'][$columnIndex]['items'] as $item) {
                        if ($item['id'] === $blockId) {
                            return $item;
                        }
                    }
                }
            }
        } else {
            foreach ($this->blocks as $block) {
                if ($block['id'] === $blockId) {
                    return $block;
                }
            }
        }

        return null;
    }

    /**
     * Open editor for a block.
     */
    public function editBlock(string $blockId, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $block = $this->findBlock($blockId, $parentId, $columnIndex);

        if (! $block) {
            return;
        }

        $this->editingBlockId = $blockId;
        $this->editingBlockData = [
            'block' => $block,
            'parentId' => $parentId,
            'columnIndex' => $columnIndex,
        ];

        $this->dispatch('open-modal', id: 'block-editor-modal');
    }

    /**
     * Handle save block data from editor component.
     */
    public function handleSaveBlockData(string $blockId, array $data, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->editingBlockId = $blockId;
        $this->editingBlockData = [
            'parentId' => $parentId,
            'columnIndex' => $columnIndex,
        ];
        $this->saveBlock($data);
    }

    /**
     * Save edited block.
     */
    public function saveBlock(array $data): void
    {
        if (! $this->editingBlockId || ! $this->editingBlockData) {
            return;
        }

        $this->saveHistory();

        $parentId = $this->editingBlockData['parentId'] ?? null;
        $columnIndex = $this->editingBlockData['columnIndex'] ?? null;

        if ($parentId !== null && $columnIndex !== null) {
            // Update in column
            foreach ($this->blocks as &$block) {
                if ($block['id'] === $parentId && isset($block['children'][$columnIndex])) {
                    foreach ($block['children'][$columnIndex]['items'] as &$item) {
                        if ($item['id'] === $this->editingBlockId) {
                            $item['data'] = $data;

                            break 2;
                        }
                    }
                }
            }
        } else {
            // Update in root
            foreach ($this->blocks as &$block) {
                if ($block['id'] === $this->editingBlockId) {
                    $block['data'] = $data;

                    // Handle column count changes for Columns component
                    if ($block['type'] === 'columns' && isset($data['columns'])) {
                        $this->adjustColumns($block, (int) $data['columns']);
                    }

                    break;
                }
            }
        }

        $this->closeEditor();
        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Adjust column count for Columns component.
     */
    protected function adjustColumns(array &$block, int $newCount): void
    {
        $currentCount = count($block['children'] ?? []);

        if ($newCount > $currentCount) {
            // Add new columns
            for ($i = $currentCount; $i < $newCount; $i++) {
                $block['children'][] = [
                    'id' => uniqid('col_'),
                    'items' => [],
                ];
            }
        } elseif ($newCount < $currentCount) {
            // Remove extra columns (from the end)
            $block['children'] = array_slice($block['children'], 0, $newCount);
        }
    }

    /**
     * Close editor modal.
     */
    public function closeEditor(): void
    {
        $this->editingBlockId = null;
        $this->editingBlockData = [];
        $this->dispatch('close-modal', id: 'block-editor-modal');
    }

    /**
     * Open component panel.
     */
    /**
     * Load a template.
     */
    public function loadTemplate(string $templateKey): void
    {
        $this->saveHistory();

        $registry = app(TemplateRegistry::class);
        $blocks = $registry->getBlocks($templateKey);

        $this->blocks = $blocks;
        $this->closeTemplatePanel();

        // Notify Filament that value has changed
        $this->emitValue();

        $template = $registry->get($templateKey);
        $this->dispatch('template-loaded', [
            'template' => $template['name'] ?? 'Template',
        ]);
    }

    /**
     * Toggle template panel (opens/closes template picker modal).
     */
    public function toggleTemplatePanel(): void
    {
        $this->showTemplatePanel = ! $this->showTemplatePanel;
        if ($this->showTemplatePanel) {
            $this->dispatch('open-modal', id: 'template-picker-modal');
        } else {
            $this->dispatch('close-modal', id: 'template-picker-modal');
        }
    }

    /**
     * Close template picker modal.
     */
    public function closeTemplatePanel(): void
    {
        $this->showTemplatePanel = false;
        $this->dispatch('close-modal', id: 'template-picker-modal');
    }

    /**
     * Add block at specific position (opens component panel).
     */
    public function addBlockAtPosition(int $position): void
    {
        $this->openComponentPanel($position);
    }

    public function openComponentPanel(?int $position = null, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->showComponentPanel = true;
        $this->insertAtPosition = $position;
        $this->insertInParent = $parentId;
        $this->insertInColumn = $columnIndex !== null ? (string) $columnIndex : null;
    }

    /**
     * Close component panel.
     */
    public function closeComponentPanel(): void
    {
        $this->showComponentPanel = false;
        $this->insertAtPosition = null;
        $this->insertInParent = null;
        $this->insertInColumn = null;
    }

    /**
     * Reorder blocks at root level.
     */
    public function reorderBlocks(array $order): void
    {
        $this->saveHistory();

        $reordered = [];

        foreach ($order as $id) {
            foreach ($this->blocks as $block) {
                if ($block['id'] === $id) {
                    $reordered[] = $block;

                    break;
                }
            }
        }

        $this->blocks = $reordered;
        $this->emitValue();
    }

    /**
     * Reorder blocks within a column.
     */
    public function reorderColumnBlocks(string $parentId, int $columnIndex, array $order): void
    {
        $this->saveHistory();

        foreach ($this->blocks as &$block) {
            if ($block['id'] === $parentId && isset($block['children'][$columnIndex])) {
                $items = $block['children'][$columnIndex]['items'];
                $reordered = [];

                foreach ($order as $id) {
                    foreach ($items as $item) {
                        if ($item['id'] === $id) {
                            $reordered[] = $item;

                            break;
                        }
                    }
                }

                $block['children'][$columnIndex]['items'] = $reordered;

                break;
            }
        }

        $this->emitValue();
    }

    /**
     * Move block up.
     */
    public function moveBlockUp(string $blockId, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->saveHistory();

        if ($parentId !== null && $columnIndex !== null) {
            foreach ($this->blocks as &$block) {
                if ($block['id'] === $parentId && isset($block['children'][$columnIndex])) {
                    $items = &$block['children'][$columnIndex]['items'];
                    $index = array_search($blockId, array_column($items, 'id'));
                    if ($index !== false && $index > 0) {
                        [$items[$index - 1], $items[$index]] = [$items[$index], $items[$index - 1]];
                        $items = array_values($items);
                    }

                    break;
                }
            }
        } else {
            $index = array_search($blockId, array_column($this->blocks, 'id'));
            if ($index !== false && $index > 0) {
                [$this->blocks[$index - 1], $this->blocks[$index]] = [$this->blocks[$index], $this->blocks[$index - 1]];
                $this->blocks = array_values($this->blocks);
            }
        }

        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Move block down.
     */
    public function moveBlockDown(string $blockId, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->saveHistory();

        if ($parentId !== null && $columnIndex !== null) {
            foreach ($this->blocks as &$block) {
                if ($block['id'] === $parentId && isset($block['children'][$columnIndex])) {
                    $items = &$block['children'][$columnIndex]['items'];
                    $index = array_search($blockId, array_column($items, 'id'));
                    if ($index !== false && $index < count($items) - 1) {
                        [$items[$index], $items[$index + 1]] = [$items[$index + 1], $items[$index]];
                        $items = array_values($items);
                    }

                    break;
                }
            }
        } else {
            $index = array_search($blockId, array_column($this->blocks, 'id'));
            if ($index !== false && $index < count($this->blocks) - 1) {
                [$this->blocks[$index], $this->blocks[$index + 1]] = [$this->blocks[$index + 1], $this->blocks[$index]];
                $this->blocks = array_values($this->blocks);
            }
        }

        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Emit value to parent form.
     */
    protected function emitValue(): void
    {
        $this->dispatch('builder-value-changed', blocks: $this->blocks);
    }

    /**
     * Get value for form binding.
     */
    public function getValue(): array
    {
        return $this->blocks;
    }

    public function render()
    {
        return view('mksine::page-builder.page-builder');
    }
}
