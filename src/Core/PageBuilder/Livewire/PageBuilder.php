<?php

namespace Miran\Mksine\Core\PageBuilder\Livewire;

use Illuminate\Support\Facades\URL;
use Livewire\Attributes\On;
use Livewire\Component;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\Components\GridLayoutComponent;
use Miran\Mksine\Core\PageBuilder\BlockPresentation;
use Miran\Mksine\Core\PageBuilder\BlockRegion;
use Miran\Mksine\Core\PageBuilder\PageBuilderPreviewStore;
use Miran\Mksine\Core\PageBuilder\PayloadNormalizer;
use Miran\Mksine\Core\PageBuilder\TemplateRegistry;
use Miran\Mksine\Models\PageBuilderSection;

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
     * Signed iframe URL for the unsaved payload. Viewport is not part of the URL.
     */
    public ?string $previewUrl = null;

    public string $previewViewport = 'desktop';

    public ?string $selectedBlockId = null;

    public string $previewHash = '';

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
     * Last block JSON for copy (read by frontend after getBlockJsonForCopy).
     */
    public ?string $copyBlockJson = null;

    /**
     * Undo/redo snapshots. Kept off the Livewire snapshot (session, not public state).
     *
     * @var array<int, array<int, mixed>>
     */
    protected array $historyStack = [];

    /**
     * Current position in history (for redo).
     */
    protected int $historyPosition = -1;

    /**
     * Maximum history size.
     */
    protected int $maxHistorySize = 50;

    /**
     * Epoch (ms) when component mounted – used to ignore spurious openPasteModal on load.
     */
    protected ?float $mountedAt = null;

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
        $this->saveHistory();
        $this->mountedAt = microtime(true);
        $this->syncPreview();
    }

    public function hydrate(): void
    {
        $this->restoreHistoryFromSession();
    }

    public function dehydrate(): void
    {
        $this->persistHistoryToSession();
    }

    protected function historySessionKey(): string
    {
        return 'mksine.page-builder.history.'.$this->getId();
    }

    protected function restoreHistoryFromSession(): void
    {
        $payload = session($this->historySessionKey(), [
            'stack' => [],
            'position' => -1,
        ]);

        $this->historyStack = is_array($payload['stack'] ?? null) ? $payload['stack'] : [];
        $this->historyPosition = is_int($payload['position'] ?? null) ? $payload['position'] : -1;
    }

    protected function persistHistoryToSession(): void
    {
        session([
            $this->historySessionKey() => [
                'stack' => $this->historyStack,
                'position' => $this->historyPosition,
            ],
        ]);
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
                $mw = (string) ($data['max_width'] ?? 'full');
                $bleed = filter_var($data['background_full_bleed'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $previewText = $bleed
                    ? trim($mw.' '.__('mksine::page_builder.container_inset_preview_bleed_suffix'))
                    : $mw;
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
        $this->mutateColumnItemsByParent($this->blocks, $parentId, $columnIndex, function (array &$items) use ($instance, $position): void {
            if ($position !== null) {
                array_splice($items, $position, 0, [$instance]);
            } else {
                $items[] = $instance;
            }

            $items = array_values($items);
        });
    }

    /**
     * Remove a block.
     */
    public function removeBlock(string $blockId, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->saveHistory();

        if ($parentId !== null && $columnIndex !== null) {
            $this->mutateColumnItemsByParent($this->blocks, $parentId, $columnIndex, function (array &$items) use ($blockId): void {
                $items = array_values(
                    array_filter($items, fn (array $item): bool => ($item['id'] ?? '') !== $blockId)
                );
            });
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
            $this->mutateColumnItemsByParent($this->blocks, $parentId, $columnIndex, function (array &$items) use ($blockId, $duplicate): void {
                $index = array_search($blockId, array_column($items, 'id'), true);
                if ($index !== false) {
                    array_splice($items, (int) $index + 1, 0, [$duplicate]);
                    $items = array_values($items);
                }
            });
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
                    unset($item);
                }
            }
            unset($column);
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
        try {
            $decoded = json_decode($clipboardData, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw new \Exception('Invalid JSON');
            }

            $block = app(PayloadNormalizer::class)->normalizeBlock($decoded, true);
            if ($block === null) {
                throw new \Exception('Invalid clipboard data');
            }

            $this->saveHistory();

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
            $items = $this->findColumnItemsList($this->blocks, $parentId, $columnIndex);
            if ($items !== null) {
                foreach ($items as $item) {
                    if (($item['id'] ?? '') === $blockId) {
                        return $item;
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
    public function editBlock(string $blockId, ?string $parentId = null, ?int $columnIndex = null, ?string $region = null, int|string|null $regionIndex = null): void
    {
        $block = $this->findBlock($blockId, $parentId, $columnIndex);

        if (! $block) {
            return;
        }

        $type = (string) ($block['type'] ?? '');
        $formData = BlockRegion::authorData($type, is_array($block['data'] ?? null) ? $block['data'] : []);
        $block['data'] = $formData;

        $regions = BlockRegion::forType($type);
        $index = is_numeric($regionIndex) ? (int) $regionIndex : null;
        if (! is_string($region) || $region === '' || ! isset($regions[$region])) {
            $region = null;
            $index = null;
        } elseif (! isset($regions[$region]['repeater'])) {
            $index = null;
        }

        $this->editingBlockId = $blockId;
        $this->selectedBlockId = $blockId;
        $this->editingBlockData = [
            'block' => $block,
            'parentId' => $parentId,
            'columnIndex' => $columnIndex,
            'region' => $region,
            'regionIndex' => $index,
            'formData' => $formData,
        ];
    }

    /**
     * Handle save block data from editor component.
     */
    #[On('saveBlockData')]
    public function handleSaveBlockData(string $blockId, array $data, ?string $parentId = null, ?int $columnIndex = null, array $presentation = []): void
    {
        $region = $this->editingBlockData['region'] ?? null;
        $regionIndex = $this->editingBlockData['regionIndex'] ?? null;
        $formData = $this->editingBlockData['formData'] ?? null;

        $this->editingBlockId = $blockId;
        $this->editingBlockData = [
            'parentId' => $parentId,
            'columnIndex' => $columnIndex,
            'region' => is_string($region) ? $region : null,
            'regionIndex' => is_int($regionIndex) ? $regionIndex : null,
            'formData' => is_array($formData) ? $formData : null,
        ];
        $this->saveBlock($data, $presentation);
    }

    /**
     * Save edited block.
     */
    public function saveBlock(array $data, array $presentation = []): void
    {
        if (! $this->editingBlockId || ! $this->editingBlockData) {
            return;
        }

        if (isset($data['presentation']) && is_array($data['presentation'])) {
            $presentation = array_merge($presentation, $data['presentation']);
            unset($data['presentation']);
        }

        $presentation = BlockPresentation::normalize($presentation);

        $this->saveHistory();

        $parentId = $this->editingBlockData['parentId'] ?? null;
        $columnIndex = $this->editingBlockData['columnIndex'] ?? null;

        $normalizer = app(PayloadNormalizer::class);
        $resolveData = function (string $type) use ($data): array {
            $region = $this->editingBlockData['region'] ?? null;
            if (! is_string($region) || $region === '') {
                return $data;
            }

            return BlockRegion::apply(
                $type,
                is_array($this->editingBlockData['formData'] ?? null) ? $this->editingBlockData['formData'] : [],
                $data,
                $region,
                is_int($this->editingBlockData['regionIndex'] ?? null) ? $this->editingBlockData['regionIndex'] : null,
            );
        };

        if ($parentId !== null && $columnIndex !== null) {
            $this->mutateColumnItemsByParent($this->blocks, $parentId, (int) $columnIndex, function (array &$items) use ($resolveData, $normalizer, $presentation): void {
                foreach ($items as &$item) {
                    if (($item['id'] ?? '') === $this->editingBlockId) {
                        $validated = $normalizer->normalizeData((string) ($item['type'] ?? ''), $resolveData((string) ($item['type'] ?? '')));
                        $item['data'] = $validated;
                        $item['presentation'] = $presentation;
                        if ((($item['type'] ?? '') === 'columns' && isset($validated['columns']))
                            || (($item['type'] ?? '') === 'grid_layout' && isset($validated['column_spans']))) {
                            $this->adjustColumns(
                                $item,
                                $this->layoutChildBucketCount((string) ($item['type'] ?? ''), $validated),
                            );
                        }

                        break;
                    }
                }
                unset($item);
            });
        } else {
            // Update in root
            foreach ($this->blocks as &$block) {
                if ($block['id'] === $this->editingBlockId) {
                    $validated = $normalizer->normalizeData((string) ($block['type'] ?? ''), $resolveData((string) ($block['type'] ?? '')));
                    $block['data'] = $validated;
                    $block['presentation'] = $presentation;

                    // Handle column count changes for Columns component
                    if (($block['type'] === 'columns' && isset($validated['columns']))
                        || ($block['type'] === 'grid_layout' && isset($validated['column_spans']))) {
                        $this->adjustColumns(
                            $block,
                            $this->layoutChildBucketCount((string) $block['type'], $validated),
                        );
                    }

                    break;
                }
            }
            unset($block);
        }

        $this->closeEditor();
        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * Child column bucket count after validation: `columns` for Columns, repeater length for `grid_layout`.
     */
    protected function layoutChildBucketCount(string $type, array $validated): int
    {
        if ($type === 'grid_layout') {
            return max(2, min(12, count($validated['column_spans'] ?? [])));
        }

        return max(2, min(12, (int) ($validated['columns'] ?? 2)));
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

        $payload = $block['data'] ?? [];

        if (($block['type'] ?? '') === 'grid_layout') {
            $spans = array_values((array) ($payload['column_spans'] ?? []));
            if (count($spans) > $newCount) {
                $spans = array_slice($spans, 0, $newCount);
            }
            while (count($spans) < $newCount) {
                $spans[] = ['span' => 6];
            }
            $payload['column_spans'] = $spans;
            $block['data'] = GridLayoutComponent::validate($payload);
        }
    }

    /**
     * Close editor modal.
     */
    #[On('closeEditor')]
    public function closeEditor(): void
    {
        $this->editingBlockId = null;
        $this->editingBlockData = [];
        $this->selectedBlockId = null;
        $this->dispatch('builder-editor-closed');
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
        $blocks = app(PayloadNormalizer::class)->normalize($registry->getBlocks($templateKey));

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
    public function addBlockAtPosition(int $position, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $this->openComponentPanel($position, $parentId, $columnIndex);
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
     * Apply a client order without dropping blocks the client omitted.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, mixed>  $order
     * @return list<array<string, mixed>>
     */
    protected function mergePreservedOrder(array $items, array $order): array
    {
        $byId = [];
        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $item;
            }
        }

        $reordered = [];
        $seen = [];
        foreach ($order as $id) {
            if (! is_string($id) || ! isset($byId[$id]) || isset($seen[$id])) {
                continue;
            }
            $reordered[] = $byId[$id];
            $seen[$id] = true;
        }

        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            if (is_string($id) && $id !== '' && isset($seen[$id])) {
                continue;
            }
            $reordered[] = $item;
        }

        return $reordered;
    }

    /**
     * Reorder blocks at root level.
     */
    #[On('builder:reorder')]
    public function reorderBlocks(array $order): void
    {
        $this->saveHistory();

        $this->blocks = $this->mergePreservedOrder($this->blocks, $order);
        $this->emitValue();
    }

    /**
     * Reorder blocks within a column.
     */
    #[On('builder:reorderColumn')]
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
     * Walk the tree and mutate the `items` list for the column whose parent block id is $parentId.
     *
     * @param  array<int, array<string, mixed>>  $blockList
     * @param  callable(array<int, array<string, mixed>> &$items): void  $mutator
     */
    protected function mutateColumnItemsByParent(array &$blockList, string $parentId, int $columnIndex, callable $mutator): void
    {
        foreach ($blockList as &$block) {
            if (($block['id'] ?? '') === $parentId && isset($block['children'][$columnIndex]['items']) && is_array($block['children'][$columnIndex]['items'])) {
                $targetItems = &$block['children'][$columnIndex]['items'];
                $mutator($targetItems);

                unset($block);

                return;
            }
            if (! empty($block['children']) && is_array($block['children'])) {
                foreach ($block['children'] as &$col) {
                    if (! isset($col['items']) || ! is_array($col['items'])) {
                        continue;
                    }
                    $itemsRef = &$col['items'];
                    $this->mutateColumnItemsByParent($itemsRef, $parentId, $columnIndex, $mutator);
                }
                unset($col);
            }
        }
        unset($block);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blockList
     */
    protected function applyColumnReorder(array &$blockList, string $parentId, int $columnIndex, array $order): bool
    {
        foreach ($blockList as &$block) {
            if (($block['id'] ?? '') === $parentId && isset($block['children'][$columnIndex]['items']) && is_array($block['children'][$columnIndex]['items'])) {
                $items = $block['children'][$columnIndex]['items'];
                $block['children'][$columnIndex]['items'] = $this->mergePreservedOrder($items, $order);

                unset($block);

                return true;
            }

            if (! empty($block['children']) && is_array($block['children'])) {
                foreach ($block['children'] as &$col) {
                    if (! isset($col['items']) || ! is_array($col['items'])) {
                        continue;
                    }
                    $itemsRef = &$col['items'];
                    if ($this->applyColumnReorder($itemsRef, $parentId, $columnIndex, $order)) {
                        unset($col, $block);

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
            $this->mutateColumnItemsByParent($this->blocks, $parentId, $columnIndex, function (array &$items) use ($blockId): void {
                $index = array_search($blockId, array_column($items, 'id'), true);
                if ($index !== false && $index > 0) {
                    [$items[$index - 1], $items[$index]] = [$items[$index], $items[$index - 1]];
                    $items = array_values($items);
                }
            });
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
            $this->mutateColumnItemsByParent($this->blocks, $parentId, $columnIndex, function (array &$items) use ($blockId): void {
                $index = array_search($blockId, array_column($items, 'id'), true);
                if ($index !== false && $index < count($items) - 1) {
                    [$items[$index], $items[$index + 1]] = [$items[$index + 1], $items[$index]];
                    $items = array_values($items);
                }
            });
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
     * The drop index counts siblings with the dragged block removed.
     * Same-list drags use that index, so an unchanged order must not be recorded.
     */
    private function sameListDropChangesOrder(string $blockId, ?string $parentId, ?int $columnIndex, int $newIndex): bool
    {
        $items = $parentId === null
            ? $this->blocks
            : ($this->findColumnItemsList($this->blocks, $parentId, (int) $columnIndex) ?? []);

        $ids = [];
        $current = null;
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = (string) ($item['id'] ?? '');
            $ids[] = $id;
            if ($id === $blockId) {
                $current = $index;
            }
        }

        if ($current === null) {
            return false;
        }

        $without = $ids;
        array_splice($without, $current, 1);
        $newIndex = max(0, min($newIndex, count($without)));
        array_splice($without, $newIndex, 0, [$blockId]);

        return $without !== $ids;
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

        if ($fromParentId === $toParentId && $fromColumnIndex === $toColumnIndex && ! $this->sameListDropChangesOrder($blockId, $fromParentId, $fromColumnIndex, $newIndex)) {
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
                unset($root);

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
                    unset($col);

                    return $out;
                }
            }
            foreach ($col['items'] as $ii => &$item) {
                $removed = $this->removeBlockFromNodeChildren($item, $blockId);
                if ($removed !== null) {
                    $col['items'][$ii] = $item;
                    unset($item, $col);

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
                unset($root);

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
            if (! isset($col['items']) || ! is_array($col['items'])) {
                continue;
            }
            foreach ($col['items'] as &$item) {
                if ($this->insertIntoNode($item, $parentId, $columnIndex, $index, $block)) {
                    unset($item, $col);

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
        $this->syncPreview();
    }

    public function setPreviewViewport(string $viewport): void
    {
        $this->previewViewport = in_array($viewport, ['desktop', 'tablet', 'mobile'], true) ? $viewport : 'desktop';
    }

    public function selectBlock(string $blockId): void
    {
        $this->selectedBlockId = $blockId;
    }

    public function syncPreview(): void
    {
        $userId = auth()->id();

        if (! is_int($userId) && ! (is_string($userId) && ctype_digit((string) $userId))) {
            $this->previewUrl = null;

            return;
        }

        $payload = app(PayloadNormalizer::class)->normalize($this->blocks);
        $hash = md5(json_encode($payload) ?: '[]');

        if ($this->previewHash === $hash && is_string($this->previewUrl) && $this->previewUrl !== '') {
            return;
        }

        $token = app(PageBuilderPreviewStore::class)->put((int) $userId, $payload);
        $this->previewHash = $hash;
        $this->previewUrl = URL::temporarySignedRoute(
            'mksine.page-builder.preview',
            now()->addMinutes(10),
            ['token' => $token],
        );
    }

    public function reorderNavigator(array $order, ?string $parentId = null, ?int $columnIndex = null): void
    {
        if ($parentId === null || $columnIndex === null) {
            $this->reorderBlocks($order);

            return;
        }

        $this->reorderColumnBlocks($parentId, $columnIndex, $order);
    }

    public function moveNavigatorBlock(string $blockId, string $direction, ?string $parentId = null, ?int $columnIndex = null): void
    {
        $items = ($parentId === null || $columnIndex === null)
            ? $this->blocks
            : $this->findColumnItemsList($this->blocks, $parentId, $columnIndex);

        if (! is_array($items)) {
            return;
        }

        $ids = [];
        foreach ($items as $item) {
            if (is_array($item) && is_string($item['id'] ?? null)) {
                $ids[] = $item['id'];
            }
        }

        $index = array_search($blockId, $ids, true);
        if ($index === false) {
            return;
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
        if (! isset($ids[$swapWith])) {
            return;
        }

        [$ids[$index], $ids[$swapWith]] = [$ids[$swapWith], $ids[$index]];
        $this->reorderNavigator($ids, $parentId, $columnIndex);
    }

    public function insertSection(int $sectionId, ?int $position = null): void
    {
        $this->addBlock('section_ref', $position);

        $index = $position ?? array_key_last($this->blocks);
        if (! is_int($index) || ! isset($this->blocks[$index]) || ($this->blocks[$index]['type'] ?? '') !== 'section_ref') {
            return;
        }

        $this->blocks[$index]['data']['section_id'] = $sectionId;
        $normalized = app(PayloadNormalizer::class)->normalizeBlock($this->blocks[$index]);
        if ($normalized !== null) {
            $this->blocks[$index] = $normalized;
        }

        $this->emitValue();
    }

    public function unlinkSection(string $blockId): void
    {
        $snapshot = $this->findBlockSnapshot($blockId);
        if ($snapshot === null || ($snapshot['type'] ?? '') !== 'section_ref') {
            return;
        }

        $section = PageBuilderSection::query()->find((int) ($snapshot['data']['section_id'] ?? 0));
        if (! $section instanceof PageBuilderSection || ! is_array($section->builder_payload)) {
            return;
        }

        $copies = app(PayloadNormalizer::class)->normalize($section->builder_payload, true);
        $this->saveHistory();
        $this->replaceBlock($this->blocks, $blockId, $copies);
        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    public function updateHeadingText(string $blockId, string $text): void
    {
        $snapshot = $this->findBlockSnapshot($blockId);
        if ($snapshot === null || ($snapshot['type'] ?? '') !== 'heading') {
            return;
        }

        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        $plain = mb_substr($plain, 0, 255);
        $data = is_array($snapshot['data'] ?? null) ? $snapshot['data'] : [];

        if (($data['text'] ?? '') === $plain) {
            return;
        }

        $data['text'] = $plain;
        $snapshot['data'] = app(PayloadNormalizer::class)->normalizeData('heading', $data);
        $this->saveHistory();
        $this->replaceBlock($this->blocks, $blockId, [$snapshot]);
        $this->dispatch('builder:updated');
        $this->emitValue();
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  list<array<string, mixed>>  $replacement
     */
    protected function replaceBlock(array &$nodes, string $blockId, array $replacement): bool
    {
        foreach ($nodes as $index => $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['id'] ?? '') === $blockId) {
                array_splice($nodes, $index, 1, $replacement);

                return true;
            }
        }

        foreach ($nodes as &$node) {
            if (! is_array($node) || empty($node['children']) || ! is_array($node['children'])) {
                continue;
            }

            foreach ($node['children'] as &$column) {
                if (! isset($column['items']) || ! is_array($column['items'])) {
                    continue;
                }

                if ($this->replaceBlock($column['items'], $blockId, $replacement)) {
                    return true;
                }
            }
        }

        unset($node, $column);

        return false;
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
