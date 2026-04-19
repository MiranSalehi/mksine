import Sortable from 'sortablejs';

let rootSortable = null;
const columnSortables = new Map();

/** Shared group so blocks can move between root and nested column drop zones. */
const BUILDER_GROUP = {
  name: 'mksine-page-builder',
  pull: true,
  put: true,
};

function getLivewireId() {
  const list = document.getElementById('blocks-list');
  if (!list) return null;
  const root = list.closest('[wire\\:id]');
  return root ? root.getAttribute('wire:id') : null;
}

function destroySortables() {
  if (rootSortable) {
    rootSortable.destroy();
    rootSortable = null;
  }
  columnSortables.forEach((s) => s.destroy());
  columnSortables.clear();
}

/**
 * Block id for a Sortable row: root list wraps each block in a wire:key div without data-block-id;
 * column rows are the block card root which has data-block-id on itself.
 */
function blockIdFromSortableRow(el) {
  if (!el) return null;
  if (el.hasAttribute?.('data-block-id')) {
    return el.getAttribute('data-block-id');
  }
  return el.querySelector?.('[data-block-id]')?.getAttribute('data-block-id') ?? null;
}

/**
 * Root list: only each top-level block wrapper (skip insertion-point rows).
 */
function getRootBlockOrder(listEl) {
  const order = [];
  if (!listEl) return order;
  for (const child of listEl.children) {
    if (child.classList.contains('insertion-point')) continue;
    const id = blockIdFromSortableRow(child);
    if (id) order.push(id);
  }
  return order;
}

/**
 * Column drop zone: only direct child cards (not nested blocks inside a container).
 */
function getColumnBlockOrder(colEl) {
  const order = [];
  if (!colEl) return order;
  for (const child of colEl.children) {
    const id = blockIdFromSortableRow(child);
    if (id) order.push(id);
  }
  return order;
}

function resolveSortableContext(containerEl) {
  if (!containerEl) return null;
  if (containerEl.id === 'blocks-list') {
    return { kind: 'root', parentId: null, columnIndex: null };
  }
  if (containerEl.matches?.('[data-sortable-column]')) {
    const parentId = containerEl.getAttribute('data-parent-id');
    const columnIndex = colIndexParse(containerEl.getAttribute('data-column-index'));
    if (parentId != null && columnIndex !== null) {
      return { kind: 'column', parentId, columnIndex };
    }
  }
  return null;
}

function colIndexParse(raw) {
  if (raw === null || raw === '') return null;
  const n = parseInt(raw, 10);
  return Number.isNaN(n) ? null : n;
}

function handleSortableEnd(evt) {
  const livewireId = getLivewireId();
  if (!livewireId || typeof window.Livewire === 'undefined') return;

  const blockId = blockIdFromSortableRow(evt.item);
  if (!blockId) return;

  const fromCtx = resolveSortableContext(evt.from);
  const toCtx = resolveSortableContext(evt.to);
  if (!fromCtx || !toCtx) return;

  const lw = window.Livewire.find(livewireId);

  const same =
    fromCtx.kind === toCtx.kind &&
    fromCtx.parentId === toCtx.parentId &&
    fromCtx.columnIndex === toCtx.columnIndex;

  if (same) {
    if (toCtx.kind === 'root') {
      const listEl = document.getElementById('blocks-list');
      const order = getRootBlockOrder(listEl);
      if (order.length) lw.call('reorderBlocks', order);
    } else {
      const order = getColumnBlockOrder(evt.to);
      if (order.length) lw.call('reorderColumnBlocks', toCtx.parentId, toCtx.columnIndex, order);
    }
    return;
  }

  let newIndex = evt.newIndex;
  if (toCtx.kind === 'root') {
    const listEl = document.getElementById('blocks-list');
    const order = getRootBlockOrder(listEl);
    const i = order.indexOf(blockId);
    newIndex = i === -1 ? 0 : i;
  } else if (toCtx.kind === 'column') {
    const order = getColumnBlockOrder(evt.to);
    const i = order.indexOf(blockId);
    if (i !== -1) {
      newIndex = i;
    }
  }

  lw.call(
    'moveBlockAfterDrag',
    blockId,
    fromCtx.kind === 'root' ? null : fromCtx.parentId,
    fromCtx.kind === 'root' ? null : fromCtx.columnIndex,
    toCtx.kind === 'root' ? null : toCtx.parentId,
    toCtx.kind === 'root' ? null : toCtx.columnIndex,
    newIndex
  );
}

function initPageBuilderSortable() {
  const listEl = document.getElementById('blocks-list');
  if (!listEl) return;

  destroySortables();

  const livewireId = getLivewireId();
  if (!livewireId || typeof window.Livewire === 'undefined') return;

  rootSortable = new Sortable(listEl, {
    handle: '[data-sortable-handle]',
    filter: '.insertion-point',
    preventOnFilter: false,
    draggable: '> div:not(.insertion-point)',
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    dragClass: 'sortable-drag',
    animation: 200,
    group: BUILDER_GROUP,
    onEnd: handleSortableEnd,
  });

  listEl.querySelectorAll('[data-sortable-column]').forEach((colEl) => {
    const parentId = colEl.getAttribute('data-parent-id');
    const columnIndex = colEl.getAttribute('data-column-index');
    if (parentId == null || columnIndex == null) return;
    const existing = columnSortables.get(colEl);
    if (existing) existing.destroy();
    const sortable = new Sortable(colEl, {
      handle: '[data-sortable-handle]',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass: 'sortable-drag',
      animation: 200,
      group: BUILDER_GROUP,
      onEnd: handleSortableEnd,
    });
    columnSortables.set(colEl, sortable);
  });
}

export function setupPageBuilderSortable() {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initPageBuilderSortable());
  } else {
    initPageBuilderSortable();
  }

  document.addEventListener('livewire:init', () => {
    initPageBuilderSortable();
    if (window.Livewire && window.Livewire.hook) {
      window.Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
          setTimeout(initPageBuilderSortable, 50);
        });
      });
    }
  });

  document.addEventListener('livewire:navigated', () => {
    setTimeout(initPageBuilderSortable, 100);
  });
}
