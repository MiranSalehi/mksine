@php
    $locale = app()->getLocale();
    $textDirection = in_array($locale, ['fa', 'ar', 'ku', 'he'], true) ? 'rtl' : 'ltr';

    /*
     * Editor chrome (outlines, insert buttons, block toolbars) uses namespaced --mks-editor-* tokens so it
     * never collides with the storefront theme's own variables. Server-side defaults come from Filament's
     * colour registry; the script below swaps in the admin panel's live palette and radii from the parent page.
     */
    $editorPalettes = \Illuminate\Support\Arr::only(\Filament\Support\Facades\FilamentColor::getColors(), ['primary', 'gray', 'danger']);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $textDirection }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mksine::page_builder.canvas_preview') }}</title>
    @themeAssets
    <style>
        :root {
            @foreach ($editorPalettes as $editorPaletteName => $editorShades)
                @foreach ($editorShades as $editorShade => $editorShadeValue)
                    --mks-editor-{{ $editorPaletteName }}-{{ $editorShade }}: {{ $editorShadeValue }};
                @endforeach
            @endforeach
            --mks-editor-radius-md: 0.375rem;
            --mks-editor-radius-lg: 0.5rem;
        }
    </style>
    <script>
        (function () {
            try {
                if (window.parent === window) {
                    return;
                }
                const styles = window.parent.getComputedStyle(window.parent.document.documentElement);
                const target = document.documentElement.style;
                const copy = function (from, to) {
                    const value = styles.getPropertyValue(from).trim();
                    if (value !== '') {
                        target.setProperty(to, value);
                    }
                };
                ['primary', 'gray', 'danger'].forEach(function (palette) {
                    [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach(function (shade) {
                        copy('--' + palette + '-' + shade, '--mks-editor-' + palette + '-' + shade);
                    });
                });
                ['sm', 'md', 'lg', 'xl', '2xl'].forEach(function (size) {
                    copy('--radius-' + size, '--mks-editor-radius-' + size);
                });
            } catch (error) {
                // Cross-origin or detached frame: keep the server-side defaults.
            }
        })();
    </script>
    <style>
        [data-mksine-block-id] {
            position: relative;
            display: flow-root;
            cursor: pointer;
            margin: 8px;
            outline: 1px solid var(--mks-editor-gray-200);
            background: white;
            transition: outline-color 120ms ease;
        }
        [data-mksine-block-id]:hover,
        [data-mksine-block-id]:focus-within {
            outline-color: var(--mks-editor-primary-400);
        }
        [data-mksine-block-id]:hover:has([data-mksine-block-id]:hover),
        [data-mksine-block-id]:focus-within:has([data-mksine-block-id]:focus-within),
        [data-mksine-block-id]:hover:has(.mksine-insert:hover),
        [data-mksine-block-id]:focus-within:has(.mksine-insert:focus-within) {
            outline-color: var(--mks-editor-gray-200);
        }
        [data-mksine-block-id].is-selected,
        [data-mksine-block-id].is-selected:hover:has([data-mksine-block-id]:hover),
        [data-mksine-block-id].is-selected:hover:has(.mksine-insert:hover) {
            outline: 2px solid var(--mks-editor-primary-700);
        }
        .mksine-region-hot { outline: 1px dashed var(--mks-editor-primary-400); outline-offset: 2px; }
        [data-mksine-inline="heading"] { cursor: text; }
        .mksine-insert {
            position: relative;
            z-index: 30;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 16px;
            transition: height 120ms ease;
        }
        .mksine-insert::before {
            content: "";
            position: absolute;
            left: 16px;
            right: 16px;
            top: 50%;
            height: 1px;
            background: color-mix(in oklab, var(--mks-editor-primary-400) 55%, transparent);
            transition: background-color 120ms ease;
        }
        .mksine-insert button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            width: 18px;
            height: 18px;
            border: 0;
            border-radius: 9999px;
            background: var(--mks-editor-primary-50);
            color: var(--mks-editor-primary-500);
            font: 600 12px/1 system-ui, sans-serif;
            padding: 0;
            cursor: pointer;
            box-shadow: inset 0 0 0 1px var(--mks-editor-primary-200);
            transition: background-color 120ms ease, color 120ms ease;
        }
        .mksine-insert-label { display: none; }
        .mksine-insert:hover,
        .mksine-insert:focus-within,
        .mksine-insert.is-visible {
            height: 32px;
        }
        .mksine-insert:hover::before,
        .mksine-insert:focus-within::before {
            background: color-mix(in oklab, var(--mks-editor-primary-600) 70%, transparent);
        }
        .mksine-insert:hover button,
        .mksine-insert:focus-within button,
        .mksine-insert.is-visible button {
            width: auto;
            height: auto;
            gap: 6px;
            padding: 6px 10px;
        }
        .mksine-insert:hover .mksine-insert-label,
        .mksine-insert:focus-within .mksine-insert-label,
        .mksine-insert.is-visible .mksine-insert-label {
            display: inline;
        }
        .mksine-insert:hover button,
        .mksine-insert:focus-within button {
            background: var(--mks-editor-primary-600);
            color: white;
            box-shadow: none;
        }
        .mksine-insert-plus {
            display: inline-flex;
            width: auto;
            height: auto;
            align-items: center;
            justify-content: center;
            background: transparent;
            color: inherit;
            font-size: 14px;
        }
        .mksine-insert:hover .mksine-insert-plus,
        .mksine-insert:focus-within .mksine-insert-plus,
        .mksine-insert.is-visible .mksine-insert-plus {
            width: 16px;
            height: 16px;
            border-radius: 9999px;
            background: white;
            color: var(--mks-editor-primary-600);
        }
        .mksine-canvas-tools {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 4px;
            height: 26px;
            padding: 0 8px;
            background: var(--mks-editor-gray-50);
            color: var(--mks-editor-gray-600);
            border-bottom: 1px solid var(--mks-editor-gray-200);
            transition: background-color 120ms ease, color 120ms ease, border-color 120ms ease;
        }
        [data-mksine-block-id].is-selected > .mksine-canvas-tools {
            background: var(--mks-editor-primary-50);
            color: var(--mks-editor-primary-800);
            border-bottom-color: var(--mks-editor-primary-200);
        }
        .mksine-canvas-tools button {
            display: none;
        }
        [data-mksine-block-id]:hover > .mksine-canvas-tools button,
        [data-mksine-block-id]:focus-within > .mksine-canvas-tools button,
        [data-mksine-block-id].is-selected > .mksine-canvas-tools button,
        [data-mksine-block-id].is-dragging > .mksine-canvas-tools button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        [data-mksine-block-id]:hover:has([data-mksine-block-id]:hover) > .mksine-canvas-tools button,
        [data-mksine-block-id]:focus-within:has([data-mksine-block-id]:focus-within) > .mksine-canvas-tools button,
        [data-mksine-block-id]:hover:has(.mksine-insert:hover) > .mksine-canvas-tools button,
        [data-mksine-block-id]:focus-within:has(.mksine-insert:focus-within) > .mksine-canvas-tools button {
            display: none;
        }
        .mksine-canvas-name {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font: 600 12px/1.2 system-ui, sans-serif;
            cursor: grab;
            touch-action: none;
        }
        [data-mksine-block-id].is-dragging {
            opacity: 0.35;
            outline-style: dashed;
            pointer-events: none;
        }
        body.is-canvas-dragging,
        body.is-canvas-dragging * { cursor: grabbing; }
        .mksine-drag-ghost {
            position: fixed;
            z-index: 80;
            left: 0;
            top: 0;
            max-width: 240px;
            padding: 6px 10px;
            border-radius: var(--mks-editor-radius-lg);
            background: var(--mks-editor-primary-700);
            color: white;
            font: 600 12px/1.2 system-ui, sans-serif;
            box-shadow: 0 10px 24px color-mix(in oklab, var(--mks-editor-primary-900) 35%, transparent);
            pointer-events: none;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .mksine-drop-line {
            height: 4px;
            margin: 8px;
            border-radius: 9999px;
            background: var(--mks-editor-primary-700);
            box-shadow: 0 0 0 4px var(--mks-editor-primary-100);
            pointer-events: none;
        }
        [data-mksine-drop].is-drop-target {
            outline: 2px solid var(--mks-editor-primary-600);
            outline-offset: 2px;
        }
        .mksine-canvas-tools button {
            width: 18px;
            height: 18px;
            border: 0;
            border-radius: var(--mks-editor-radius-md);
            background: var(--mks-editor-primary-100);
            color: var(--mks-editor-primary-800);
            cursor: pointer;
            font: 700 13px/1 system-ui, sans-serif;
        }
        .mksine-canvas-tools button:hover { background: var(--mks-editor-primary-200); }
        .mksine-canvas-tools button[data-mksine-action="remove"]:hover { background: var(--mks-editor-danger-700); }
        @media (prefers-reduced-motion: reduce) {
            [data-mksine-block-id],
            .mksine-insert,
            .mksine-insert::before,
            .mksine-insert button,
            .mksine-canvas-tools {
                transition: none;
            }
        }
        html:has(.mksine-canvas-empty),
        html:has(.mksine-canvas-empty) body,
        html:has(.mksine-canvas-empty) .builder-content {
            height: 100%;
            margin: 0;
            background: white;
        }
        .mksine-canvas-empty {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-sizing: border-box;
            height: 100%;
            margin: 0;
            padding: 0 16px;
            background: white;
            color: var(--mks-editor-gray-700);
            font: 500 13px/1 system-ui, sans-serif;
        }
        .mksine-canvas-empty p {
            margin: 0;
        }
        .mksine-canvas-empty button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 0;
            border-radius: 9999px;
            background: var(--mks-editor-primary-600);
            color: white;
            font: 600 13px/1 system-ui, sans-serif;
            padding: 7px 12px 7px 7px;
            cursor: pointer;
        }
        .mksine-canvas-empty button:hover {
            background: var(--mks-editor-primary-700);
        }
        .mksine-canvas-empty button:focus-visible {
            outline: 2px solid var(--mks-editor-primary-600);
            outline-offset: 2px;
        }
        .mksine-canvas-empty button span {
            display: inline-flex;
            width: 16px;
            height: 16px;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: white;
            color: var(--mks-editor-primary-600);
            font-size: 14px;
        }
    </style>
</head>
<body class="{{ $textDirection === 'rtl' ? 'rtl' : 'ltr' }}">
    <div class="builder-content space-y-0">
        @include('mksine::page-builder.render.payload', [
            'payload' => $payload,
            'preview' => true,
            'canvas' => true,
        ])
    </div>
    <script>
        (function () {
            const source = 'mksine-page-builder';

            let hot = null;

            function hotspot(node) {
                if (!node || !node.closest) {
                    return null;
                }
                const block = node.closest('[data-mksine-block-id]');
                if (!block) {
                    return null;
                }
                const region = node.closest('[data-mksine-region]');
                if (region && region.closest('[data-mksine-block-id]') === block) {
                    return region;
                }
                return null;
            }

            function markSelected(block) {
                document.querySelectorAll('[data-mksine-block-id].is-selected').forEach(function (el) {
                    el.classList.remove('is-selected');
                });
                if (block) {
                    block.classList.add('is-selected');
                }

            }

            document.addEventListener('mousemove', function (event) {
                const next = hotspot(event.target);
                if (hot === next) {
                    return;
                }
                if (hot) {
                    hot.classList.remove('mksine-region-hot');
                }
                hot = next;
                if (hot) {
                    hot.classList.add('mksine-region-hot');
                }
            });

            function placement(node) {
                if (!node || !node.dataset) {
                    return { parentId: '', columnIndex: null };
                }
                const parentId = node.dataset.mksineParentId || node.dataset.mksineParent || '';
                const raw = node.dataset.mksineColumnIndex !== undefined
                    ? node.dataset.mksineColumnIndex
                    : node.dataset.mksineColumn;
                const columnIndex = raw === undefined || raw === '' || Number.isNaN(Number(raw)) ? null : Number(raw);
                return { parentId: parentId, columnIndex: columnIndex };
            }

            function dropIndex(zone, pointerY, dragged) {
                const blocks = [...zone.children].filter(function (el) {
                    return el.matches('[data-mksine-block-id]') && el !== dragged;
                });
                for (let i = 0; i < blocks.length; i++) {
                    const rect = blocks[i].getBoundingClientRect();
                    if (pointerY < rect.top + rect.height / 2) {
                        return i;
                    }
                }
                return blocks.length;
            }

            let drag = null;

            function dragGhost(name) {
                let ghost = document.querySelector('.mksine-drag-ghost');
                if (!ghost) {
                    ghost = document.createElement('div');
                    ghost.className = 'mksine-drag-ghost';
                    document.body.appendChild(ghost);
                }
                ghost.textContent = name;
                return ghost;
            }

            function placeDropLine(zone, index, dragged) {
                let line = document.querySelector('.mksine-drop-line');
                if (!zone) {
                    if (line) {
                        line.remove();
                    }
                    return;
                }
                if (!line) {
                    line = document.createElement('div');
                    line.className = 'mksine-drop-line';
                }
                const blocks = [...zone.children].filter(function (el) {
                    return el.matches('[data-mksine-block-id]') && el !== dragged;
                });
                const before = blocks[index] || null;
                if (before) {
                    zone.insertBefore(line, before);
                } else {
                    zone.appendChild(line);
                }
            }

            function clearDragChrome() {
                const ghost = document.querySelector('.mksine-drag-ghost');
                const line = document.querySelector('.mksine-drop-line');
                if (ghost) {
                    ghost.remove();
                }
                if (line) {
                    line.remove();
                }
            }

            document.addEventListener('pointerdown', function (event) {
                const handle = event.target.closest('[data-mksine-drag]');
                if (!handle || event.button !== 0) {
                    return;
                }
                const block = handle.closest('[data-mksine-block-id]');
                if (!block) {
                    return;
                }
                drag = {
                    block: block,
                    x: event.clientX,
                    y: event.clientY,
                    active: false,
                    zone: null,
                    pointerId: event.pointerId,
                };
                event.preventDefault();
            });

            document.addEventListener('pointermove', function (event) {
                if (!drag || event.pointerId !== drag.pointerId) {
                    return;
                }
                if (!drag.active) {
                    if (Math.abs(event.clientX - drag.x) + Math.abs(event.clientY - drag.y) < 6) {
                        return;
                    }
                    drag.active = true;
                    drag.block.classList.add('is-dragging');
                    document.body.classList.add('is-canvas-dragging');
                    const name = drag.block.querySelector('.mksine-canvas-name');
                    dragGhost(name ? name.textContent.trim() : '');
                }
                const ghost = document.querySelector('.mksine-drag-ghost');
                if (ghost) {
                    ghost.style.transform = 'translate(' + (event.clientX + 12) + 'px, ' + (event.clientY + 12) + 'px)';
                }
                const hit = document.elementFromPoint(event.clientX, event.clientY);
                let zone = hit && hit.closest ? hit.closest('[data-mksine-drop]') : null;
                if (zone && drag.block.contains(zone)) {
                    zone = null;
                }
                if (drag.zone && drag.zone !== zone) {
                    drag.zone.classList.remove('is-drop-target');
                }
                drag.zone = zone;
                if (zone) {
                    zone.classList.add('is-drop-target');
                    placeDropLine(zone, dropIndex(zone, event.clientY, drag.block), drag.block);
                } else {
                    placeDropLine(null, 0, drag.block);
                }
            });

            document.addEventListener('pointerup', function (event) {
                if (!drag || event.pointerId !== drag.pointerId) {
                    return;
                }
                const state = drag;
                drag = null;
                document.body.classList.remove('is-canvas-dragging');
                clearDragChrome();
                state.block.classList.remove('is-dragging');
                if (state.zone) {
                    state.zone.classList.remove('is-drop-target');
                }
                if (state.active) {
                    window.__mksineSuppressClick = true;
                }
                if (!state.active || !state.zone) {
                    return;
                }
                const fromColumn = state.block.dataset.mksineColumnIndex;
                const toColumn = state.zone.dataset.mksineColumn;
                window.parent.postMessage({
                    source: source,
                    type: 'move',
                    movedId: state.block.dataset.mksineBlockId,
                    fromParentId: state.block.dataset.mksineParentId || '',
                    fromColumnIndex: fromColumn === undefined || fromColumn === '' ? null : Number(fromColumn),
                    toParentId: state.zone.dataset.mksineParent || '',
                    toColumnIndex: toColumn === undefined || toColumn === '' ? null : Number(toColumn),
                    index: dropIndex(state.zone, event.clientY, state.block),
                }, window.location.origin);
            });

            document.addEventListener('click', function (event) {
                if (window.__mksineSuppressClick || (event.target.closest && event.target.closest('[data-mksine-drag]'))) {
                    window.__mksineSuppressClick = false;
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                const insert = event.target.closest('[data-mksine-insert]');
                if (insert) {
                    event.preventDefault();
                    event.stopPropagation();
                    const place = placement(insert);
                    window.parent.postMessage({
                        source: source,
                        type: 'insert',
                        position: Number(insert.dataset.mksineInsert),
                        parentId: place.parentId,
                        columnIndex: place.columnIndex,
                    }, window.location.origin);
                    return;
                }
                const action = event.target.closest('[data-mksine-action]');
                if (action) {
                    event.preventDefault();
                    event.stopPropagation();
                    const owner = action.closest('[data-mksine-block-id]');
                    if (!owner) {
                        return;
                    }
                    const place = placement(owner);
                    if (action.dataset.mksineAction === 'settings') {
                        markSelected(owner);
                    }
                    window.parent.postMessage({
                        source: source,
                        type: action.dataset.mksineAction,
                        blockId: owner.dataset.mksineBlockId,
                        parentId: place.parentId,
                        columnIndex: place.columnIndex,
                    }, window.location.origin);
                    return;
                }
                const inline = event.target.closest('[data-mksine-inline]');
                const block = event.target.closest('[data-mksine-block-id]');
                if (!block) {
                    return;
                }
                if (inline) {
                    markSelected(block);
                    return;
                }
                const target = hotspot(event.target);
                const region = target && target.dataset.mksineRegion ? target : null;
                const index = region && region.dataset.mksineRegionIndex !== undefined && region.dataset.mksineRegionIndex !== ''
                    ? Number(region.dataset.mksineRegionIndex)
                    : null;
                const place = placement(block);
                markSelected(block);
                event.preventDefault();
                window.parent.postMessage({
                    source: source,
                    type: 'open',
                    blockId: block.dataset.mksineBlockId,
                    parentId: place.parentId,
                    columnIndex: place.columnIndex,
                    region: region ? region.dataset.mksineRegion : '',
                    index: index,
                }, window.location.origin);
            });

            document.addEventListener('paste', function (event) {
                const inline = event.target.closest('[data-mksine-inline="heading"]');
                if (!inline) {
                    return;
                }
                event.preventDefault();
                const text = event.clipboardData ? event.clipboardData.getData('text/plain') : '';
                document.execCommand('insertText', false, text);
            });

            document.addEventListener('keydown', function (event) {
                const inline = event.target.closest('[data-mksine-inline="heading"]');
                if (!inline || event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();
                const block = inline.closest('[data-mksine-block-id]');
                if (!block) {
                    return;
                }
                window.parent.postMessage({
                    source: source,
                    type: 'inline-heading',
                    blockId: block.dataset.mksineBlockId,
                    text: inline.innerText || '',
                }, window.location.origin);
            });
        })();
    </script>
</body>
</html>
