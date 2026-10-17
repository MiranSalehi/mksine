---
title: Headless page document
---

# Headless page document

A React, Vue, Angular, or Next.js storefront does not use the Blade renderer. It reads one JSON document and draws the blocks itself. MKSine does not ship components for those frameworks. The admin editor stays in Filament.

The same three `GET` routes serve every client. `schema_version` is `1`.

| Method and path | Route name | Body |
| --- | --- | --- |
| `GET /api/mksine/pages` | `mksine.pages.api.index` | Published pages: `id`, `title`, `slug`, `type`, `updated_at` (Atom). |
| `GET /api/mksine/pages/blocks` | `mksine.pages.api.blocks` | Block catalog registered on this boot. |
| `GET /api/mksine/pages/{slug}` | `mksine.pages.api.show` | One page document. `{slug}` is one path segment. |

`/blocks` is registered before `/{slug}`, so `blocks` is not a page slug.

The routes use the `web` middleware and `throttle:120,1`. They are public reads. There is no token and no write route. Creating and editing pages stays in the admin panel.

Only a published page is returned. That is `status = published` and `published_at` null or already due. A draft, a missing slug, or a page scheduled for later is `404` with `{"message":"Not found"}`, not an HTML error page.

Laravel's default CORS config allows `api/*`. A browser on another origin can `GET` these URLs. If you published `config/cors.php` and removed that path, add `api/mksine/pages` and `api/mksine/pages/*`.

## Page document

`GET /api/mksine/pages/home`:

```json
{
  "schema_version": 1,
  "page": {
    "id": 1,
    "title": "Home",
    "slug": "home",
    "type": "builder",
    "show_page_header": true,
    "builder_content_width": "contained",
    "meta_title": null,
    "meta_description": null,
    "content": null,
    "blocks": [
      {
        "id": "block_a",
        "type": "heading",
        "data": { "text": "Hello", "level": "h2" },
        "children": null,
        "presentation": {
          "anchor": "",
          "spacing": "none",
          "visibility": "always",
          "padding": "none",
          "background": "none",
          "align": "start",
          "width": "full",
          "text_color": "",
          "background_color": "",
          "border_width": "none",
          "border_color": "",
          "border_radius": "none"
        }
      }
    ]
  }
}
```

`show_page_header` is `true` when the column is null. A blank `builder_content_width` becomes `contained`. `full` means the Blade theme does not wrap the page in the contained column; your layout should do the same.

`type: "builder"` puts the tree in `blocks` and sets `content` to `null`. Any other type leaves `blocks` empty and puts the stored HTML in `content`. Render that HTML as HTML. Do not treat it as text, and do not execute it.

## What you render

Fetch the document, then walk `page.blocks` in order. Branch on `type`. Ignore `schema_version` values you do not know; do not guess a newer shape.

```js
const response = await fetch("https://example.com/api/mksine/pages/home");
if (response.status === 404) {
  // missing, draft, or not yet published
}
const document = await response.json();

for (const block of document.page.blocks) {
  // switch (block.type) and render your own component
}
```

That `fetch` is the whole integration. In Next.js, call it from a server component or a route handler. In Vue, Angular, or a browser React app, call it from the page that displays the CMS page. Map each `type` to a component in that framework. A block type you have not mapped should be skipped, not rendered as raw JSON.

`data` is the validated field bag for that type. Keys differ per block. Read `GET /api/mksine/pages/blocks` for `type`, `name`, `category`, `supports_children`, and `defaults`. The catalog is not a form schema. Rich-text fields inside `data` are HTML. Link fields have already dropped `javascript:` URLs. Heading `level` is an allowed tag (`h1`–`h6`); an unknown level arrives as `h2`.

`presentation` is the shared appearance object on every block. It is not inside `data`. Apply it on the wrapper you draw around the block:

| Key | Allowed values |
| --- | --- |
| `anchor` | Empty, or an id matching `[a-z][a-z0-9_-]{0,63}` without a leading `#`. |
| `spacing`, `padding` | `none`, `sm`, `md`, `lg`. Spacing is the block's outer margin. |
| `visibility` | `always`, `hide_mobile`, `hide_desktop`. |
| `background` | `none`, `muted`, `brand`. |
| `align` | `start`, `left`, `center`, `right`, `end`. |
| `width` | `full`, `contained`. |
| `text_color`, `background_color`, `border_color` | Empty, or a hex color. |
| `border_width`, `border_radius` | `none`, `sm`, `md`, `lg`. |

## Children

A leaf block has `children: null`. A container's `children` is a list of buckets. Each bucket is `{ "id": "...", "items": [ /* blocks, same shape */ ] }`. Render `items` with the same `type` switch. Do not assume a flat list of child blocks.

`card` always has three buckets. Index `0` is the header, `1` is the body, and `2` is the footer. `data.show_header` and `data.show_footer` hide a band. Hiding it does not remove the bucket and does not shift the indexes.

`section_ref` is not in the document. The API replaces a saved section with that section's blocks. A missing section, or a section that references itself, is omitted. There is no placeholder for the client to draw.

A block type that is not registered on the server is dropped before the response is built. A theme or plugin block must be registered on every boot, or a headless client never sees it. Register it from the theme `theme.php` or the plugin `boot()`, the same way the editor does. See [Creating a block](creating-a-block.md).

## What this API does not do

- It does not save a page, reorder blocks, or open the editor.
- It does not return the signed preview iframe. That URL is for the logged-in editor only.
- It does not include Blade, theme CSS, or `mksine-blocks.css`. Those belong to the Laravel theme. A headless client owns its own styles. Use `presentation` for the shared tokens instead of scraping the package stylesheet.
- It does not version each block. `schema_version` versions the document envelope. Block `data` stays compatible by ignoring unknown keys and defaulting missing ones.

## See also

- [Rendering](rendering.md) — Blade loop used by Laravel themes
- [Nesting](nesting.md)
- [Validation](validation.md)
- [Creating a block](creating-a-block.md)
