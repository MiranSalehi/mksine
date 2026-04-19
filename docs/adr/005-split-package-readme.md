---
title: ADR 005: Split monolithic package README into docs/
---

# ADR 005: Split monolithic package README into `docs/`

## Context

The package README grew into a very large single file. That hurts navigation, review, and AI retrieval (chunking). Packagist still needs a concise entry point.

## Decision

- Archive the monolithic README under `docs/archive/README-v1-monolithic.md`.
- Maintain a **short** package `README.md` with install summary and links into `docs/`.
- Add focused files: golden path, hooks contract, commands, security, troubleshooting, validation.

## Consequences

- Some narrative sections (page builder, menus, deep hook theory) remain in the archive until extracted into smaller docs.
- Contributors must update the right file; PR template or review habit should pair README index changes with `docs/` updates.

## References

- [00-introduction.md](../00-introduction.md)
- [archive/README-v1-monolithic.md](../archive/README-v1-monolithic.md)
