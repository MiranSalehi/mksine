---
title: ADR 004: No mks-plugin:publish-vendor in core
---

# ADR 004: No `mks-plugin:publish-vendor` in core

## Context

Third-party Composer packages ship files that must land inside a plugin directory for ZIP-only hosting. A one-size-fits-all Artisan command in core is hard because presets differ per plugin.

## Decision

Keep **vendor materialization** in **per-plugin** commands using `PluginVendorPublishRunner` and `publishes/*.json` manifests (each plugin documents its own preset command).

## Consequences

- Core stays smaller; each plugin documents its own `publish-vendor` command.
- Authors must read plugin-specific README sections, not only core docs.

## References

- `Miran\Mksine\Core\Plugins\Publishing\PluginVendorPublishRunner`
- [30-commands-reference.md](../30-commands-reference.md) (plugin vendor publish is not a core command)
