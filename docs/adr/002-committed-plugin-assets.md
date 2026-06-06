---
title: ADR 002: Commit published plugin assets for ZIP deploy
---

# ADR 002: Commit published plugin assets for ZIP deploy

## Context

Many deployments ship a pre-built Laravel tree without Node or plugin-level Composer on the server.

## Decision

Treat `public/plugins/{id}/` (after `mks-plugin:publish`) and `lang/vendor/{id}/` (after `publish-lang`) as **first-class artifacts** to commit when the team follows “build locally, deploy zip” workflows.

## Consequences

- Repositories are larger; PRs must review built assets for supply-chain and accidental secrets.
- CI can optionally verify `npm run build` reproducibility where needed.

## References

- `PluginPublishCommand`, `PluginPublishLangCommand`
- [Plugin golden path](../guides/plugins/golden-path.md)
