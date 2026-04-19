# Changelog

All notable changes to `mksine` will be documented in this file.

## Unreleased

- Docs describe plugin paths via `config('mksine.plugins_path')` / `{plugin_root}` instead of assuming a monorepo `plugins/` tree; canonical text lives under `packages/mksine/docs/`.
- Add `docs/60-deployment-hosting.md` (document root, Apache/nginx, Livewire routing, `mks:release-archive` public allowlist); extend troubleshooting, validation checklist, overview, README, commands reference, and `DEVELOPER-SLO.md` version coupling.
- Split developer documentation into `docs/` (golden path, hooks contract, commands reference, security, troubleshooting, validation checklist, ADRs). Package `README.md` is now a short index; the previous monolithic README is archived under `docs/archive/README-v1-monolithic.md`.
- Add internal audit note `docs/internal/AUDIT-2026-04.md` (commands and `mks:discover` paths vs code).
- Document developer audience and SLO in `docs/DEVELOPER-SLO.md`.

## 1.0.0 - 202X-XX-XX

- initial release
