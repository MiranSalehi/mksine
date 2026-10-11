---
title: Page builder editor
---

# Page builder editor

The Filament field is the Livewire component `mksine::page-builder`. Authors work on the canvas. There is no structure list and no left navigator.

## Toolbar and frame

The toolbar holds the template picker, undo, redo, fullscreen, and **Add Component**. Fullscreen is a workspace: back, the page title, viewport width, and the same actions, with the canvas filling the rest of the screen. Selecting a block only marks it on the canvas. The settings control opens its existing schema in a slide-over, not beside the canvas. The component library stays on the registry: categories remain, and a search box filters names in the browser without a new request. Plugin components without a description still show their name and icon. Closing the panel clears the selection and does not write the page. Escape closes that panel before it leaves fullscreen. Escape does not leave fullscreen while the component library, template picker, or paste box is open. Undo and redo stay in the session. They are not `page_builder_revisions` rows.

Desktop, tablet, and mobile only change the frame width, in the browser, without a new request. The viewport is not part of the preview URL. Hover, selection, and insert points use a short color transition. Drag still shows the name ghost and the drop line, and it does not open the editor.

## Preview iframe

When the page has at least one block, the canvas is an iframe of the unsaved tree. `PageBuilderPreviewStore` keeps that tree in cache for 10 minutes, keyed by the authenticated user and a random token. The iframe loads `mksine.page-builder.preview`, which requires `web`, `auth`, and `signed`. Guests cannot open it.

The preview document renders through the same `mksine::page-builder.render.block` dispatcher as the theme page, and it loads the active theme's assets. A block that looks right in the iframe can still look different on the storefront when the theme wraps the loop (`.prose`, `page.before_content`, and similar). See [Rendering](rendering.md).

`PayloadNormalizer` drops types that are not in `ComponentRegistry` before the preview is stored. If a saved page uses a block the current boot did not register, that block disappears from the canvas on the next edit. Register it on every boot. See [Concepts and feature flag](concepts-and-feature-flag.md#built-in-blocks).

Changing the payload swaps the iframe URL. The editor restores the iframe's scroll position. Do not put the scroll offset in the preview URL.

## Empty page

When `blocks` is empty, the editor does not mount the iframe. The frame is one row: a block icon, "Your page is empty", a one-line hint, and **Add Component**. That button calls `addBlockAtPosition(0)` on the Livewire component and opens the picker. While that request runs, the plus icon is replaced by a spinner in the same box, so the button does not change size. It does not depend on a message from inside an iframe.

## Insert, move, remove

With blocks present, the preview paints an insert control before the first block, between blocks, and after the last. The same control is painted inside each column, grid region, and inset container. A click inside the iframe posts a message to the parent origin:

```json
{ "source": "mksine-page-builder", "type": "insert", "position": 0, "parentId": "", "columnIndex": null }
```

A nested insert sets `parentId` to the container block id and `columnIndex` to the region. The parent calls `addBlockAtPosition` with those arguments, so the picker inserts into that region. `remove`, `move-up`, `move-down`, and `duplicate` carry the same placement. Dragging the block name posts `move` and does not open the editor. While the pointer moves, a label follows the cursor and a line shows the landing slot. Each canvas block is a card: a label row in normal flow, then the component. The label does not cover the component. Action buttons show when that block is hovered. Insert controls stay visible as a light line and a small plus. The words appear on the hovered gap, and on an empty region. They turn solid only on that gap. A drag shows the landing line without turning every insert solid. The hovered card is the innermost one: ancestors keep the quiet outline. Clicking a card marks it selected with a stronger outline and a violet name row. An empty region shows its add control inside the parent card. The parent calls `moveBlockAfterDrag`, which can place a root block inside a container region. Root inserts leave `parentId` empty. The same channel carries `select`, region edit, and inline heading updates. The listener is registered on the builder root, including when the field is mounted after the first paint (a new page switched from **simple** to **builder**). Ignore messages whose `source` is not `mksine-page-builder` or whose `origin` is not the admin origin.

Index `0` is a real position. Do not treat it as "missing".

## Regions

A canvas click opens the block modal. Built-in types declare named regions in `BlockRegion::map()`. A plugin or theme block adds its own by overriding `regionDefinitions()`:

```php
public static function regionDefinitions(): array
{
    return [
        'title' => ['fields' => ['title']],
        'plans' => ['repeater' => 'plans'],
    ];
}
```

Leave the method on `BaseBuilderComponent` alone. An empty map means the click opens the whole form.

Mark the preview target with `BlockRegion::attributes($region, $preview, $index)`. The preview flag is false on the public page, so those attributes are not rendered there.

Saving a region merges into the stored block. Fields outside the region stay. A repeater region plus an index replaces that row only.

Heading text is the exception: it is edited in place on the canvas. The rich-text block stays in the modal.

## Display fields on a new builder page

`PageForm` shows `show_page_header` and `builder_content_width` when the type is `builder`. Switching a new page to `builder` seeds:

- `builder_payload` to `[]` when it is not already an array
- `show_page_header` to `false` when it is still `null`
- `builder_content_width` to `full` when it is blank

An existing payload, or an explicit `true` / `false` header flag, is left alone. Themes still read these columns. See [Rendering](rendering.md).

## See also

- [Concepts and feature flag](concepts-and-feature-flag.md)
- [Creating a block](creating-a-block.md)
- [Rendering](rendering.md)
- [Validation](validation.md)
