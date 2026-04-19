---
title: Versioning
description: Semver policy applied to the MKSine public surface.
order: 0
---

# Versioning

`miran/mksine` follows [semver 2.0.0](https://semver.org). The contract operates on the public surface defined in [API stability](../reference/stability.md). This page explains how the rules map to that surface.

## What changes warrant which version bump?

| Change | Bump |
|--------|------|
| Adding a new public interface, manager, command, or config key | minor |
| Adding a new option/argument to a public command (with a safe default) | minor |
| Changing the default value of a public config key in a way that alters runtime behavior | minor (and called out in [Upgrade guide](upgrade-guide.md)) |
| Renaming a public class, method, command signature, or required option | **major** |
| Removing a public class, method, command, or config key | **major** |
| Changing the **required** signature of a public interface method | **major** |
| Adding a method with a default implementation in a base class | minor |
| Changing internal classes (anything not in [stability](../reference/stability.md)) | patch or minor at our discretion |
| Bug fixes that do not change behavior contracts | patch |

## Database schema

Migrations published from MKSine create the schema for built-in resources. Schema **rules**:

- New columns added with safe defaults are minor.
- Renaming or dropping columns is **major**.
- Adding or removing tables that back a public Eloquent model is **major**.

Direct SQL queries against MKSine tables are **not** part of the public surface. Use the Eloquent models documented in [API stability](../reference/stability.md) instead. Schema introspection should treat MKSine columns as opaque outside the documented attributes.

## Deprecation policy

1. Mark the API as deprecated in a minor release. Add a `@deprecated` PHPDoc tag and emit a `trigger_error` or log warning at call sites.
2. Document the deprecation in `CHANGELOG.md` and [Upgrade guide](upgrade-guide.md).
3. Keep the deprecated alias or behavior for **at least one** minor release.
4. Remove no earlier than the next major release.

Pre-1.0 releases are excused from semver guarantees per the spec but should still call out breakages in `CHANGELOG.md`.

## Release cadence

- **Patch:** anytime, no schedule.
- **Minor:** when meaningful additive surface accumulates (no fixed cadence).
- **Major:** only when accumulated breakages justify the migration cost; expect months between majors.

## How consumers should track changes

1. Pin in `composer.json` per your tolerance: `"miran/mksine": "^X.Y"` accepts patches and minors.
2. After `composer update miran/mksine`, read `CHANGELOG.md` and the matching section of [Upgrade guide](upgrade-guide.md).
3. Re-run [validation checklist](../operations/validation-checklist.md) after upgrades that touch your panel’s extension points.

## Internal versioning

Internal classes (anything not in [stability](../reference/stability.md)) may move at any time. If you must reach into them, pin to an exact version in `composer.json` and review every release diff.
