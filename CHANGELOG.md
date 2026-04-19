# Changelog

All notable changes to `mksine` will be documented in this file.

See [`docs/meta/upgrade-guide.md`](docs/meta/upgrade-guide.md) for migration notes per release and [`docs/meta/versioning.md`](docs/meta/versioning.md) for the semver policy.

## Unreleased

- Restructure developer documentation under `packages/mksine/docs/` into the final tree: `00-introduction.md`, `01-installation.md`, `02-quickstart.md`, plus `concepts/`, `guides/`, `reference/`, `operations/`, and `meta/` directories. Add `_nav.yml` as the SSG-agnostic sidebar source.
- Introduce `docs/reference/stability.md` to define the public API surface (interfaces, facades, managers, commands, configuration) covered by semver.
- Move and expand prior single-page docs:
  - `docs/40-security-auth.md` → `docs/guides/auth/user-subclass.md`
  - `docs/50-troubleshooting.md` → `docs/operations/troubleshooting.md`
  - `docs/60-deployment-hosting.md` → `docs/operations/deployment-hosting.md`
  - `docs/99-validation-checklist.md` → `docs/operations/validation-checklist.md`
  - `docs/DEVELOPER-SLO.md` → `docs/meta/slo.md`
- Document plugin paths via `config('mksine.plugins_path')` / `{plugin_root}` instead of a monorepo-specific `plugins/` tree.
- Update `README.md` and `tests/Unit/MksinePackageDocsTest.php` to mirror the new tree.

## 1.0.0 - 202X-XX-XX

- initial release
