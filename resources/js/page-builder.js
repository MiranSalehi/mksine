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
  const list = document.getElementById('blocks-list');
  const wireEl = list?.closest('[wire\\:id]');
  const wireId = wireEl?.getAttribute('wire:id');
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
      window.Livewire?.find(document.getElementById('blocks-list')?.closest('[wire\\:id]')?.getAttribute('wire:id'))?.call('undo');
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'z' && e.shiftKey) {
      e.preventDefault();
      window.Livewire?.find(document.getElementById('blocks-list')?.closest('[wire\\:id]')?.getAttribute('wire:id'))?.call('redo');
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'y') {
      e.preventDefault();
      window.Livewire?.find(document.getElementById('blocks-list')?.closest('[wire\\:id]')?.getAttribute('wire:id'))?.call('redo');
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'v') {
      e.preventDefault();
      if (Date.now() - (window.__mksinePageLoadAt || 0) < BOOT_GUARD_MS) return;
      const list = document.getElementById('blocks-list');
      const wireId = list?.closest('[wire\\:id]')?.getAttribute('wire:id');
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

export function setupPageBuilderGlobals() {
  if (typeof window.__mksinePageLoadAt === 'undefined') {
    window.__mksinePageLoadAt = Date.now();
  }
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
