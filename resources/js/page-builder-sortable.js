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
 * Block id for a Sortable row: root list uses direct block cards with data-block-id; nested columns use the same card root.
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
 * Column drop zone: only direct child cards with data-block-id (skip toolbar + empty-state UI).
 */
function getColumnBlockOrder(colEl) {
  const order = [];
  if (!colEl) return order;
  /** Only direct block roots (Sortable draggable items); skip toolbar + empty-state UI. */
  for (const child of colEl.children) {
    if (!child.matches?.('[data-block-id]')) continue;
    const id = child.getAttribute('data-block-id');
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

/** Column host for reading order: after drop, item.parentElement is authoritative (fallbackOnBody breaks evt.to). */
function resolveColumnHostEl(evt) {
  const listEl = document.getElementById('blocks-list');
  if (!listEl) return null;
  const itemParent = evt.item?.parentElement;
  if (itemParent?.matches?.('[data-sortable-column]') && listEl.contains(itemParent)) {
    return itemParent;
  }
  if (evt.to?.matches?.('[data-sortable-column]') && listEl.contains(evt.to)) {
    return evt.to;
  }
  const nearTo = evt.to?.closest?.('[data-sortable-column]');
  if (nearTo && listEl.contains(nearTo)) {
    return nearTo;
  }
  const nearItem = evt.item?.closest?.('[data-sortable-column]');
  if (nearItem && listEl.contains(nearItem)) {
    return nearItem;
  }
  return null;
}

function resolveFromContext(evt) {
  const listEl = document.getElementById('blocks-list');
  if (!listEl) return null;
  if (evt.from?.id === 'blocks-list') {
    return { kind: 'root', parentId: null, columnIndex: null };
  }
  let ctx = resolveSortableContext(evt.from);
  if (ctx) return ctx;
  const col = evt.from?.closest?.('[data-sortable-column]');
  if (col && listEl.contains(col)) {
    const parentId = col.getAttribute('data-parent-id');
    const columnIndex = colIndexParse(col.getAttribute('data-column-index'));
    if (parentId != null && columnIndex !== null) {
      return { kind: 'column', parentId, columnIndex };
    }
  }
  return null;
}

function resolveToContext(evt) {
  const listEl = document.getElementById('blocks-list');
  if (!listEl) return null;

  /** DOM parent after insert is reliable for nested column ↔ column moves (same container slot → inner columns). */
  const itemParent = evt.item?.parentElement;
  if (itemParent && (itemParent === listEl || itemParent.id === 'blocks-list')) {
    return { kind: 'root', parentId: null, columnIndex: null };
  }
  if (
    itemParent?.matches?.('[data-sortable-column]') &&
    listEl.contains(itemParent)
  ) {
    const parentId = itemParent.getAttribute('data-parent-id');
    const columnIndex = colIndexParse(itemParent.getAttribute('data-column-index'));
    if (parentId != null && columnIndex !== null) {
      return { kind: 'column', parentId, columnIndex };
    }
  }

  if (evt.to?.id === 'blocks-list') {
    return { kind: 'root', parentId: null, columnIndex: null };
  }
  let ctx = resolveSortableContext(evt.to);
  if (ctx) return ctx;
  const colHost = resolveColumnHostEl(evt);
  if (colHost) {
    const parentId = colHost.getAttribute('data-parent-id');
    const columnIndex = colIndexParse(colHost.getAttribute('data-column-index'));
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

/**
 * Ignore drags that start inside a nested column drop zone so #blocks-list Sortable does not
 * grab the top-level wrapper (e.g. container) when the user meant to drag a block inside grid/columns.
 */
function rootSortableIgnoreNestedColumnsFilter(evt) {
  const t = evt.target;
  if (typeof t.closest !== 'function') return false;
  if (t.closest('.insertion-point')) return true;
  return Boolean(t.closest('[data-sortable-column]'));
}

/**
 * Nested layout: multiple [data-sortable-column] ancestors. Only the column that owns the drag handle may react.
 */
function columnSortableOwnershipFilter(evt, _target, sortable) {
  const t = evt.target;
  if (typeof t.closest !== 'function') return false;
  const handle = t.closest('[data-sortable-handle]');
  if (!handle) return false;
  const ownerColumn = handle.closest('[data-sortable-column]');
  if (!ownerColumn) return false;
  return ownerColumn !== sortable.el;
}

function handleSortableEnd(evt) {
  const livewireId = getLivewireId();
  if (!livewireId || typeof window.Livewire === 'undefined') return;

  const blockId = blockIdFromSortableRow(evt.item);
  if (!blockId) return;

  const fromCtx = resolveFromContext(evt);
  const toCtx = resolveToContext(evt);
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
      const colEl = resolveColumnHostEl(evt);
      const order = colEl ? getColumnBlockOrder(colEl) : getColumnBlockOrder(evt.to);
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
    const colEl = resolveColumnHostEl(evt);
    const order = colEl ? getColumnBlockOrder(colEl) : getColumnBlockOrder(evt.to);
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
    filter: rootSortableIgnoreNestedColumnsFilter,
    preventOnFilter: false,
    /** Direct children are block cards or wrappers; block roots are divs (insertion rows use class insertion-point). */
    draggable: '> div:not(.insertion-point)',
    swapThreshold: 0.65,
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
      filter: columnSortableOwnershipFilter,
      preventOnFilter: false,
      draggable: '[data-block-id]',
      swapThreshold: 0.65,
      emptyInsertThreshold: 80,
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
