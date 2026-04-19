---
title: ADR 001: Two hook families (DB-synced vs runtime)
---

# ADR 001: Two hook families (DB-synced vs runtime)

## Context

MKSine extensions use class-based listeners synced to `mks_hooks` **and** runtime mechanisms (`Hooks::`, `ResourceHookManager`, theme actions). Conflating them confuses authors and AI tools.

## Decision

Document and maintain **two explicit families**:

1. **Discover + DB:** `mks:discover` + `mks_hooks` for class-based listeners under configured paths.
2. **Runtime / template:** PHP registration and Blade theme hooks without DB sync.

## Consequences

- Plugin authors must add `hooks.discovery_paths` for plugin listener trees.
- UI that implies “all hooks are in the database” is incorrect and must not be documented in core without qualification.

## References

- `DiscoverHooksCommand`
- `config/mksine.php` → `hooks.discovery_paths`
- [20-hooks-contract.md](../20-hooks-contract.md)
