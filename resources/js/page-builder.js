/**
 * Page Builder Alpine.js data and global handlers.
 */

const BOOT_GUARD_MS = 1200;

export function pageBuilderAlpineData() {
  return {
    fullScreen: false,

    openPasteBox(position) {
      const loadAt = window.__mksinePageLoadAt;
      const elapsed = loadAt ? Date.now() - loadAt : 0;
      if (!loadAt || elapsed < BOOT_GUARD_MS) return;
      this.$wire.openPasteModal(position ?? null);
    },

    onBuilderPreviewMessage(event) {
      const data = event?.data;
    if (!data || data.source !== 'mksine-page-builder' || data.type === 'move') return;
      if (event.origin && event.origin !== window.location.origin) return;
      const parentId = data.parentId || null;
      const columnIndex = data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null;
      if (data.type === 'insert') {
        this.$wire.addBlockAtPosition(data.position === 0 || data.position ? data.position : 0, parentId, columnIndex);
        return;
      }
      if (data.type === 'remove' && data.blockId) {
        this.$wire.removeBlock(data.blockId, parentId, columnIndex);
        return;
      }
      if (data.type === 'move-up' && data.blockId) {
        this.$wire.moveBlockUp(data.blockId, parentId, columnIndex);
        return;
      }
      if (data.type === 'move-down' && data.blockId) {
        this.$wire.moveBlockDown(data.blockId, parentId, columnIndex);
        return;
      }
      if (data.type === 'duplicate' && data.blockId) {
        this.$wire.duplicateBlock(data.blockId, parentId, columnIndex);
        return;
      }
      if (data.type === 'inline-heading') {
        this.$wire.updateHeadingText(data.blockId, data.text ?? '');
        return;
      }
      if (data.type === 'select') {
        return;
      }
      if (data.blockId) {
        this.$wire.editBlock(data.blockId, parentId, columnIndex, data.region || null, data.index === 0 || data.index ? data.index : null);
      }
    },
  };
}

function copyToClipboard(str) {
  if (!str) return;
  if (navigator.clipboard?.writeText) {
    navigator.clipboard.writeText(str).catch(() => fallbackCopy(str));
  } else {
    fallbackCopy(str);
  }
}

function fallbackCopy(str) {
  const ta = document.createElement('textarea');
  ta.value = str;
  document.body.appendChild(ta);
  ta.select();
  document.execCommand('copy');
  ta.remove();
}

function handleCopyToClipboard(event) {
  const payload = event?.detail ?? event?.data ?? event;
  const str = typeof payload === 'object' && payload?.data != null
    ? payload.data
    : (typeof payload === 'string' ? payload : JSON.stringify(payload));
  copyToClipboard(str);
}

function openPasteBoxVanilla(position) {
  if (Date.now() - (window.__mksinePageLoadAt || 0) < BOOT_GUARD_MS) return;
  window.dispatchEvent(new CustomEvent('pagebuilder:show-paste-box', { detail: { position } }));
}

let lastRequestPasteAt = 0;

async function handleRequestPaste(event) {
  if (Date.now() - (window.__mksinePageLoadAt || 0) < BOOT_GUARD_MS) return;
  if (Date.now() - lastRequestPasteAt < 400) return;
  lastRequestPasteAt = Date.now();
  const payload = event?.detail ?? event;
  let position = null;
  if (payload != null) {
    if (Array.isArray(payload)) position = payload[0]?.position ?? payload[0];
    else position = payload.position;
  }
  if (!navigator.clipboard || typeof navigator.clipboard.readText !== 'function') {
    openPasteBoxVanilla(position);
    return;
  }
  let text = '';
  try {
    text = await navigator.clipboard.readText();
  } catch {
    openPasteBoxVanilla(position);
    return;
  }
  if (!text?.trim()) return;
  const wireId = document.getElementById('mksine-page-builder-root')?.getAttribute('wire:id');
  if (wireId && typeof window.Livewire !== 'undefined') {
    try {
      window.Livewire.find(wireId).call('pasteBlock', text, position);
    } catch {}
  }
}

function setupKeydownHandlers() {
  document.addEventListener('keydown', (e) => {
    if (document.activeElement?.tagName === 'INPUT' || document.activeElement?.tagName === 'TEXTAREA' || document.activeElement?.isContentEditable) return;
    if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !e.shiftKey) {
      e.preventDefault();
      window.Livewire?.find(document.getElementById('mksine-page-builder-root')?.getAttribute('wire:id'))?.call('undo');
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'z' && e.shiftKey) {
      e.preventDefault();
      window.Livewire?.find(document.getElementById('mksine-page-builder-root')?.getAttribute('wire:id'))?.call('redo');
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'y') {
      e.preventDefault();
      window.Livewire?.find(document.getElementById('mksine-page-builder-root')?.getAttribute('wire:id'))?.call('redo');
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'v') {
      e.preventDefault();
      if (Date.now() - (window.__mksinePageLoadAt || 0) < BOOT_GUARD_MS) return;
      const wireId = document.getElementById('mksine-page-builder-root')?.getAttribute('wire:id');
      if (wireId && typeof window.Livewire !== 'undefined') {
        if (navigator.clipboard && typeof navigator.clipboard.readText === 'function') {
          navigator.clipboard.readText()
            .then((text) => {
              if (text?.trim()) window.Livewire.find(wireId).call('pasteBlock', text, null);
            })
            .catch(() => openPasteBoxVanilla(null));
        } else {
          openPasteBoxVanilla(null);
        }
      }
    }
  });
}

export function registerPageBuilderAlpine() {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('mksinePageBuilder', pageBuilderAlpineData);
  });
}

function bindPreviewBridge() {
  if (window.__mksineBuilderPreviewBound) {
    return;
  }
  window.__mksineBuilderPreviewBound = true;

  window.__mksinePreviewScroll = window.__mksinePreviewScroll || 0;
  window.__mksineCapturePreviewScroll = function () {
    const frame = document.querySelector('#mksine-page-builder-root iframe');
    window.__mksinePageScroll = window.scrollY || 0;
    if (!frame?.contentWindow) {
      return;
    }
    try {
      const doc = frame.contentDocument;
      const y = frame.contentWindow.scrollY || 0;
      const scrollable = !!doc && (doc.documentElement.scrollHeight - doc.documentElement.clientHeight) > 80;
      if (y > 0 || scrollable) {
        window.__mksinePreviewScroll = y;
      }
    } catch {}
  };
  window.__mksineRestorePreviewScroll = function (frame) {
    const y = window.__mksinePreviewScroll || 0;
    if (!frame || !y) {
      return;
    }
    let tries = 0;
    const apply = function () {
      tries += 1;
      try {
        frame.contentWindow.scrollTo(0, y);
        if (tries < 12 && Math.abs((frame.contentWindow.scrollY || 0) - y) > 40) {
          setTimeout(apply, 120);
        }
      } catch {}
    };
    apply();
  };

  if (!window.__mksinePreviewScrollBound && window.Livewire?.hook) {
    window.__mksinePreviewScrollBound = true;
    window.Livewire.hook('commit', ({ component, succeed }) => {
      const root = document.getElementById('mksine-page-builder-root');
      if (!root || !component.el || (component.el !== root && !component.el.contains(root))) {
        return;
      }
      window.__mksineCapturePreviewScroll();
      const pageY = window.__mksinePageScroll || 0;
      succeed(() => {
        window.scrollTo(0, pageY);
      });
    });
  }

  window.addEventListener('message', (event) => {
    if (event.origin !== window.location.origin) {
      return;
    }
    const data = event.data;
    if (!data || data.source !== 'mksine-page-builder' || data.type === 'move') {
      return;
    }
    const wireId = document.getElementById('mksine-page-builder-root')?.getAttribute('wire:id');
    const component = wireId && window.Livewire ? window.Livewire.find(wireId) : null;
    if (!component) {
      return;
    }
    const parentId = data.parentId || null;
    const columnIndex = data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null;
    if (data.type === 'insert') {
      component.call('addBlockAtPosition', data.position === 0 || data.position ? data.position : 0, parentId, columnIndex);
      return;
    }
    if (!data.blockId) {
      return;
    }
    if (data.type === 'remove') {
      component.call('removeBlock', data.blockId, parentId, columnIndex);
      return;
    }
    if (data.type === 'move-up') {
      component.call('moveBlockUp', data.blockId, parentId, columnIndex);
      return;
    }
    if (data.type === 'move-down') {
      component.call('moveBlockDown', data.blockId, parentId, columnIndex);
      return;
    }
    if (data.type === 'duplicate') {
      component.call('duplicateBlock', data.blockId, parentId, columnIndex);
      return;
    }
    if (data.type === 'inline-heading') {
      component.call('updateHeadingText', data.blockId, data.text || '');
      return;
    }
    if (data.type === 'select') {
      return;
    }
    component.call('editBlock', data.blockId, parentId, columnIndex, data.region || null, data.index === 0 || data.index ? data.index : null);
  });
}

export function setupPageBuilderGlobals() {
  if (typeof window.__mksinePageLoadAt === 'undefined') {
    window.__mksinePageLoadAt = Date.now();
  }
  bindPreviewBridge();
  registerPageBuilderAlpine();
  document.addEventListener('copy-to-clipboard', handleCopyToClipboard);
  if (typeof window.Livewire !== 'undefined') {
    window.Livewire.on('copy-to-clipboard', handleCopyToClipboard);
  }
  document.addEventListener('request-paste', handleRequestPaste);
  if (typeof window.Livewire !== 'undefined') {
    window.Livewire.on('request-paste', handleRequestPaste);
  }
  setupKeydownHandlers();
}
