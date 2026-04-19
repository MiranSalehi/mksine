# Developer audience and success criteria

This document fixes **who the docs are for** and what “done” means for plugin work. Keep it aligned with [00-overview.md](00-overview.md) and [99-validation-checklist.md](99-validation-checklist.md).

## Primary audiences

| Audience | Typical context | What they need |
|----------|-----------------|----------------|
| **Host app team** | Monorepo or client project with `miran/mksine` | Golden path, commands reference, deploy without npm on server |
| **Third-party / partner** | Same, often without Cursor skills | Same docs inside the package (`packages/mksine/docs/`) as **canonical** source |
| **AI-assisted author** | Cursor, CLI agents, RAG over repo | Small markdown files under `docs/`, explicit contracts (hooks, discovery paths) |

Packagist consumers get **this package**; they do **not** get `.cursor/skills/`. Skills in the host repo must **link here**, not duplicate long prose.

## Service-level objective (SLO) for plugin development

**Goal:** A developer who has never read MKSine core PHP can ship a **minimal CRUD plugin** by following [10-plugin-golden-path.md](10-plugin-golden-path.md) only.

**Concrete outcomes:**

1. `php artisan mks-plugin:discover` sees the plugin.
2. `mks-plugin:install` / `activate` succeeds; no boot failure in `mks_plugins`.
3. At least one Filament resource appears in the admin panel (with appropriate permissions).
4. Migrations apply via `php artisan mks-plugin:migrate {id}` (or full app migrate, per project).
5. Built assets and lang (if used) are committed or documented for deploy: `public/plugins/{id}/`, `lang/vendor/{id}/`.

**Non-goals for the golden path:** large product domains, vendor ZIP publishing as a core command, custom auth stacks beyond what [40-security-auth.md](40-security-auth.md) describes. Cover those with your own plugin docs and team conventions.

## Version coupling

- **Docs and code:** Treat incompatible changes to `plugin.php` contract, Artisan signatures, or hook discovery as **semver-minor or major** in `CHANGELOG.md` and call them out in docs.
- **Packagist / lock file:** The behavior you read in `docs/` must match the **`miran/mksine` version pinned in `composer.lock`**. After `composer update miran/mksine`, re-read [00-overview.md](00-overview.md) (documentation version) and `CHANGELOG.md` for breaking notes.
- **Documentation version paragraph:** The canonical statement lives in [00-overview.md](00-overview.md) under “Documentation version”; keep this section aligned when release policy changes.
- **Cursor skills:** After editing canonical docs, trim skills to summaries + links so drift is visible in review (skills cannot version themselves per package release).
