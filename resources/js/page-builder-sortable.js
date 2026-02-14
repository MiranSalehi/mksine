import Sortable from 'sortablejs';

let rootSortable = null;
const columnSortables = new Map();

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

function initPageBuilderSortable() {
  const listEl = document.getElementById('blocks-list');
  if (!listEl) return;

  destroySortables();

  const livewireId = getLivewireId();
  if (!livewireId || typeof window.Livewire === 'undefined') return;

  // Root blocks list: only draggable items have [data-block-id], filter out .insertion-point
  rootSortable = new Sortable(listEl, {
    handle: '[data-sortable-handle]',
    filter: '.insertion-point',
    preventOnFilter: false,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    dragClass: 'sortable-drag',
    animation: 200,
    onEnd(evt) {
      const order = Array.from(listEl.querySelectorAll('[data-block-id]'))
        .map((el) => el.getAttribute('data-block-id'))
        .filter(Boolean);
      if (order.length) {
        window.Livewire.find(livewireId).call('reorderBlocks', order);
      }
    },
  });

  // Column lists
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
      group: 'column-' + parentId + '-' + columnIndex,
      onEnd() {
        const order = Array.from(colEl.querySelectorAll('[data-block-id]'))
          .map((el) => el.getAttribute('data-block-id'))
          .filter(Boolean);
        if (order.length) {
          window.Livewire.find(livewireId).call('reorderColumnBlocks', parentId, parseInt(columnIndex, 10), order);
        }
      },
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
