<?php

namespace Miran\Mksine\Core\PageBuilder\Livewire;

use Livewire\Component;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\TemplateRegistry;

class PageBuilder extends Component
{
    /**
     * Guard window (ms) to prevent paste modal opening on load.
     */
    public const BOOT_GUARD_MS = 1200;
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
     * Show template picker modal (only in DOM when true).
     */
    public bool $showTemplatePanel = false;

    /**
     * Show paste modal (only in DOM when true).
     */
    public bool $showPasteModal = false;

    /**
     * Paste modal: textarea value.
     */
    public string $pasteText = '';

    /**
     * Paste modal: insert position (null = append).
     */
    public ?int $pastePosition = null;

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
     * Active category tab in the add-component modal.
     */
    public string $componentPickerTab = '';

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

    /**
     * Epoch (ms) when component mounted – used to ignore spurious openPasteModal on load.
     */
    protected ?float $mountedAt = null;

    protected $listeners = [
        'builder:reorder' => 'reorderBlocks',
        'builder:reorderColumn' => 'reorderColumnBlocks',
        'saveBlockData' => 'handleSaveBlockData',
        'closeEditor' => 'closeEditor',
    ];

    /**
     * Run at the start of every request. Reset paste modal to avoid persistence
     * when parent re-renders (e.g. form validation) and component state is reused.
     */
    public function boot(): void
    {
        $this->showPasteModal = false;
    }

    public function mount(array $value = []): void
    {
        $this->blocks = $value;
        $this->previewUrl = route('mksine.page-builder.preview');
        $this->saveHistory();
        $this->mountedAt = microtime(true);
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
     * Sorted category keys for sidebar display.
     */
    public function getSortedCategoriesProperty(): array
    {
        $categoryMeta = $this->categoryMeta;
        $orderKey = collect($categoryMeta)->mapWithKeys(fn ($m, $k) => [$k => $m['order'] ?? 99])->all();

        return collect($this->components ?? [])->keys()->sortBy(fn ($c) => $orderKey[$c] ?? 99)->values()->all();
    }

    /**
     * Category display meta (name, icon) keyed by category.
     */
    public function getCategoryDisplayMetaProperty(): array
    {
        $meta = [];
        foreach ($this->sortedCategories as $category) {
            $cm = $this->categoryMeta[$category] ?? [];
            $meta[$category] = [
                'name' => $cm['name'] ?? $category,
                'icon' => $cm['icon'] ?? 'heroicon-o-square-2-stack',
            ];
        }

        return $meta;
    }

    /**
     * Editor modal heading (component name or fallback).
     */
    public function getEditorHeadingProperty(): string
    {
        $heading = __('mksine::page_builder.edit_component');
        if (! empty($this->editingBlockData['block']['type'])) {
            $registry = app(ComponentRegistry::class);
            $compClass = $registry->get($this->editingBlockData['block']['type']);
            if ($compClass) {
                $heading = $compClass::getName();
            }
        }

        return $heading;
    }

    /**
     * Templates grouped by category for template picker.
     */
    public function getTemplatesByCategoryProperty(): array
    {
        return app(TemplateRegistry::class)->byCategory()->toArray();
    }

    /**
     * Block display info for rendering (icon class, name, supports children, preview text).
     */
    public function getBlockDisplayInfo(array $block): array
    {
        $registry = app(ComponentRegistry::class);
        $componentClass = $registry->get($block['type'] ?? '');
        $supportsChildren = $componentClass ? $componentClass::supportsChildren() : false;
        $previewText = '';
        if ($componentClass && ! empty($block['data'])) {
            $data = $block['data'];
            if (($block['type'] ?? '') === 'heading' && ! empty($data['text'])) {
                $previewText = \Illuminate\Support\Str::limit($data['text'], 40);
            } elseif (($block['type'] ?? '') === 'text' && ! empty($data['content'])) {
                $previewText = \Illuminate\Support\Str::limit(strip_tags($data['content']), 40);
            } elseif (($block['type'] ?? '') === 'button' && ! empty($data['text'])) {
                $previewText = $data['text'];
            } elseif (($block['type'] ?? '') === 'container_inset') {
                $previewText = trim(
                    ($data['padding_inline'] ?? 'md').' · '.($data['max_width'] ?? 'full')
                );
            }
        }

        return [
            'componentClass' => $componentClass,
            'supportsChildren' => $supportsChildren,
            'previewText' => $previewText,
        ];
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

        if ($this->showComponentPanel) {
            $this->closeComponentPanel();
        }
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
            'message' => __('mksine::page_builder.component_copied'),
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
     * Sanitizes JSON input (valid structure, allowed keys).
     */
    public function pasteBlock(string $clipboardData, ?int $position = null): void
    {
        $this->saveHistory();

        try {
            $decoded = json_decode($clipboardData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }
            $block = is_array($decoded) ? $decoded : [];

            if (empty($block['type']) || ! is_string($block['type'])) {
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

            $this->dispatch('paste-success', ['message' => __('mksine::page_builder.component_pasted')]);
            $this->dispatch('builder:updated');
            $this->emitValue();
        } catch (\Exception $e) {
            $this->dispatch('paste-error', ['message' => __('mksine::page_builder.invalid_clipboard_data')]);
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
     * Open paste overlay (vanilla, no Filament modal).
     * Ignores calls within ~600ms of mount to prevent spurious open on load.
     */
    public function openPasteModal(?int $position = null): void
    {
        if ($this->mountedAt !== null && (microtime(true) - $this->mountedAt) < 0.6) {
            return;
        }
        $this->pastePosition = $position;
        $this->pasteText = '';
        $this->showPasteModal = true;
    }

    public function closePasteModal(): void
    {
        $this->showPasteModal = false;
        $this->pasteText = '';
        $this->pastePosition = null;
    }

    public function submitPasteModal(): void
    {
        $text = trim($this->pasteText);
        $position = $this->pastePosition;
        $this->closePasteModal();
        if ($text !== '') {
            $this->pasteBlock($text, $position);
        }
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
     * Open editor for a block (modal only in DOM when editingBlockId is set).
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
     * Toggle template picker modal (only in DOM when showTemplatePanel=true).
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
        $this->componentPickerTab = $this->resolveInitialComponentPickerTab();
        $this->dispatch('open-modal', id: 'component-picker-modal');
    }

    /**
     * Close component panel.
     */
    public function closeComponentPanel(): void
    {
        $wasOpen = $this->showComponentPanel;
        $this->showComponentPanel = false;
        $this->insertAtPosition = null;
        $this->insertInParent = null;
        $this->insertInColumn = null;
        $this->componentPickerTab = '';
        if ($wasOpen) {
            $this->dispatch('close-modal', id: 'component-picker-modal');
        }
    }

    /**
     * Switch add-component modal tab (only to categories that have blocks).
     */
    public function setComponentPickerTab(string $tab): void
    {
        if (empty($this->components[$tab] ?? [])) {
            return;
        }

        $this->componentPickerTab = $tab;
    }

    /**
     * First category that has at least one component (for default modal tab).
     */
    protected function resolveInitialComponentPickerTab(): string
    {
        foreach ($this->sortedCategories as $category) {
            if (! empty($this->components[$category] ?? [])) {
                return $category;
            }
        }

        $first = $this->sortedCategories[0] ?? null;

        return ($first !== null && $first !== '') ? $first : 'content';
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
        if (! $this->columnItemsExist($parentId, $columnIndex)) {
            return;
        }

        $this->saveHistory();

        $blocks = &$this->blocks;
        $this->applyColumnReorder($blocks, $parentId, $columnIndex, $order);
        $this->emitValue();
    }

    protected function columnItemsExist(string $parentId, int $columnIndex): bool
    {
        return $this->findColumnItemsList($this->blocks, $parentId, $columnIndex) !== null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blockList
     * @return array<int, array<string, mixed>>|null
     */
    protected function findColumnItemsList(array $blockList, string $parentId, int $columnIndex): ?array
    {
        foreach ($blockList as $block) {
            if (($block['id'] ?? '') === $parentId && isset($block['children'][$columnIndex]['items']) && is_array($block['children'][$columnIndex]['items'])) {
                return $block['children'][$columnIndex]['items'];
            }
            if (! empty($block['children']) && is_array($block['children'])) {
                foreach ($block['children'] as $col) {
                    if (! isset($col['items']) || ! is_array($col['items'])) {
                        continue;
                    }
                    $found = $this->findColumnItemsList($col['items'], $parentId, $columnIndex);
                    if ($found !== null) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blockList
     */
    protected function applyColumnReorder(array &$blockList, string $parentId, int $columnIndex, array $order): bool
    {
        foreach ($blockList as &$block) {
            if (($block['id'] ?? '') === $parentId && isset($block['children'][$columnIndex]['items']) && is_array($block['children'][$columnIndex]['items'])) {
                $items = $block['children'][$columnIndex]['items'];
                $reordered = [];

                foreach ($order as $id) {
                    foreach ($items as $item) {
                        if (($item['id'] ?? '') === $id) {
                            $reordered[] = $item;

                            break;
                        }
                    }
                }

                $block['children'][$columnIndex]['items'] = $reordered;

                return true;
            }

            if (! empty($block['children']) && is_array($block['children'])) {
                foreach ($block['children'] as &$col) {
                    if (! isset($col['items']) || ! is_array($col['items'])) {
                        continue;
                    }
                    $itemsRef = &$col['items'];
                    if ($this->applyColumnReorder($itemsRef, $parentId, $columnIndex, $order)) {
                        return true;
                    }
                }
                unset($col);
            }
        }
        unset($block);

        return false;
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
     * Move a block between the root list and a nested column (or between columns / parents) after a Sortable drag.
     */
    public function moveBlockAfterDrag(
        string $blockId,
        ?string $fromParentId,
        mixed $fromColumnIndex,
        ?string $toParentId,
        mixed $toColumnIndex,
        int $newIndex
    ): void {
        $fromParentId = ($fromParentId !== null && $fromParentId !== '') ? $fromParentId : null;
        $toParentId = ($toParentId !== null && $toParentId !== '') ? $toParentId : null;
        $fromColumnIndex = ($fromColumnIndex === null || $fromColumnIndex === '') ? null : (int) $fromColumnIndex;
        $toColumnIndex = ($toColumnIndex === null || $toColumnIndex === '') ? null : (int) $toColumnIndex;

        if ($fromParentId === $toParentId && $fromColumnIndex === $toColumnIndex) {
            return;
        }

        $this->saveHistory();

        $snapshot = $this->findBlockSnapshot($blockId);
        if ($snapshot === null) {
            $this->emitValue();

            return;
        }

        if ($toParentId !== null && $this->blockTreeContainsId($snapshot, $toParentId)) {
            $this->emitValue();

            return;
        }

        $removed = $this->removeBlockById($blockId);
        if ($removed === null) {
            $this->emitValue();

            return;
        }

        $newIndex = max(0, $newIndex);

        if (! $this->insertBlockAtLocation($toParentId, $toColumnIndex, $newIndex, $removed)) {
            $this->insertBlockAtLocation($fromParentId, $fromColumnIndex, 0, $removed);
        }

        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findBlockSnapshot(string $blockId): ?array
    {
        foreach ($this->blocks as $b) {
            if (($b['id'] ?? '') === $blockId) {
                return $b;
            }
        }
        foreach ($this->blocks as $b) {
            $found = $this->findBlockSnapshotInNode($b, $blockId);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>|null
     */
    protected function findBlockSnapshotInNode(array $node, string $blockId): ?array
    {
        if (empty($node['children']) || ! is_array($node['children'])) {
            return null;
        }
        foreach ($node['children'] as $col) {
            foreach ($col['items'] ?? [] as $item) {
                if (($item['id'] ?? '') === $blockId) {
                    return $item;
                }
                $nested = $this->findBlockSnapshotInNode($item, $blockId);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function blockTreeContainsId(array $node, string $id): bool
    {
        if (($node['id'] ?? '') === $id) {
            return true;
        }
        if (empty($node['children']) || ! is_array($node['children'])) {
            return false;
        }
        foreach ($node['children'] as $col) {
            foreach ($col['items'] ?? [] as $item) {
                if ($this->blockTreeContainsId($item, $id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function removeBlockById(string $blockId): ?array
    {
        foreach ($this->blocks as $i => $b) {
            if (($b['id'] ?? '') === $blockId) {
                return array_splice($this->blocks, $i, 1)[0];
            }
        }
        foreach ($this->blocks as &$root) {
            $removed = $this->removeBlockFromNodeChildren($root, $blockId);
            if ($removed !== null) {
                return $removed;
            }
        }
        unset($root);

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>|null
     */
    protected function removeBlockFromNodeChildren(array &$node, string $blockId): ?array
    {
        if (empty($node['children']) || ! is_array($node['children'])) {
            return null;
        }
        foreach ($node['children'] as $ci => &$col) {
            if (! isset($col['items']) || ! is_array($col['items'])) {
                continue;
            }
            foreach ($col['items'] as $ii => $item) {
                if (($item['id'] ?? '') === $blockId) {
                    $out = array_splice($col['items'], $ii, 1)[0];
                    $col['items'] = array_values($col['items']);
                    $node['children'][$ci] = $col;

                    return $out;
                }
            }
            foreach ($col['items'] as $ii => &$item) {
                $removed = $this->removeBlockFromNodeChildren($item, $blockId);
                if ($removed !== null) {
                    $col['items'][$ii] = $item;

                    return $removed;
                }
            }
            unset($item);
        }
        unset($col);

        return null;
    }

    /**
     * @param  array<string, mixed>  $block
     */
    protected function insertBlockAtLocation(?string $parentId, ?int $columnIndex, int $index, array $block): bool
    {
        if ($parentId === null && $columnIndex === null) {
            $index = min($index, count($this->blocks));
            array_splice($this->blocks, $index, 0, [$block]);
            $this->blocks = array_values($this->blocks);

            return true;
        }
        if ($parentId === null || $columnIndex === null) {
            return false;
        }
        foreach ($this->blocks as &$root) {
            if ($this->insertIntoNode($root, $parentId, $columnIndex, $index, $block)) {
                return true;
            }
        }
        unset($root);

        return false;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function insertIntoNode(array &$node, string $parentId, int $columnIndex, int $index, array $block): bool
    {
        if (($node['id'] ?? '') === $parentId && isset($node['children'][$columnIndex]['items']) && is_array($node['children'][$columnIndex]['items'])) {
            $items = &$node['children'][$columnIndex]['items'];
            $index = min($index, count($items));
            array_splice($items, $index, 0, [$block]);
            $node['children'][$columnIndex]['items'] = array_values($items);

            return true;
        }
        if (empty($node['children']) || ! is_array($node['children'])) {
            return false;
        }
        foreach ($node['children'] as &$col) {
            $colItems = $col['items'] ?? [];
            if (! is_array($colItems)) {
                continue;
            }
            foreach ($colItems as &$item) {
                if ($this->insertIntoNode($item, $parentId, $columnIndex, $index, $block)) {
                    return true;
                }
            }
            unset($item);
        }
        unset($col);

        return false;
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
