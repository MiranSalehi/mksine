---
title: Contributing
description: How to contribute to miran/mksine and its documentation.
order: 3
---

# Contributing

`miran/mksine` is developed inside a host Laravel application as a `path` Composer package at `packages/mksine/`. This guide describes how to make a change land safely.

## What this guide covers

- Code changes (the package itself, not host-app code).
- Documentation changes (Markdown under `packages/mksine/docs/`).
- ADRs (architectural decisions).
- Tests and test fixtures.

It does not cover host-app or plugin-specific contributions; those follow the host project’s own process.

## Before you write any code

Read these in order:

1. [API stability](../reference/stability.md) — what is public, what is private, what semver promises apply.
2. [Versioning](versioning.md) — when a change is breaking and when it is not.
3. [Upgrade guide](upgrade-guide.md) — current `Unreleased` notes; your change probably needs an entry.
4. [SLO](slo.md) — performance, availability, and developer-experience targets your change must not regress.

If your change touches a public surface (a contract, a facade, a command signature, a config key, or any documented behavior), it requires:

- A test that locks the new behavior in.
- An entry in the **Upgrade guide** under `Unreleased`.
- A changelog line.

## Local development

The package lives at `packages/mksine/`. The host app maps it via a `path` repository, so any change is picked up live without reinstalling.

```bash
cd packages/mksine
composer install            # package-local dev dependencies
./vendor/bin/pest            # run package tests
```

To exercise a change end-to-end against the host app, run the app's normal commands from the project root.

## Code style

- PSR-12 + Laravel convention.
- `declare(strict_types=1);` at the top of every new file.
- Final classes by default; mark non-final with intent (you’re saying it’s a public extension point).
- Single Responsibility per class. Hook managers are an example to follow: registry, repository, dispatcher are separate.
- Throw narrowly typed exceptions with messages someone in production can act on.
- No magic. Don’t add `__call`/`__get` shortcuts to "save typing"; they break IDE tooling and obscure failure modes.
- Public methods get PHPDoc only when type-hints don’t convey intent; otherwise types are documentation.

## Testing

The package uses Pest. Tests live under `packages/mksine/tests/`.

- Every public manager method needs at least one test pinning its current behavior, even if obvious.
- Hook lifecycle changes need new entries in `tests/Unit/Hooks/HookManagerTest` and `HookDispatcherTest`.
- For features that touch the DB (plugin lifecycle, hook state repository), use the package's `TestCase` and prefer in-memory SQLite fixtures.
- For Filament resource changes, write feature tests that mount the page in isolation; do not assert on Blade output structure beyond what users actually see.

Run with:

```bash
cd packages/mksine
./vendor/bin/pest
./vendor/bin/pest --filter=HookManager
```

Architectural rules live in `tests/ArchTest.php`. Update them when you legitimately need to break a rule, but **never** silence them with `->ignoring()` to dodge a real violation.

## Pull request scope

One change per PR. Refactors that are "easier to do at the same time" go in their own PR.

A PR is reviewable when:

- The diff is < 400 lines (excluding lockfiles and generated files), or
- The diff is larger but every file’s change is the **same** mechanical refactor and easy to verify by skimming.

Bigger PRs slow review and bury defects. Split.

## Documentation changes

The documentation tree lives under `packages/mksine/docs/` and is SSG-agnostic (plain Markdown + a `_nav.yml` sidebar manifest).

- Every new `.md` file must be added to `_nav.yml` exactly once. CI enforces this.
- Cross-links are relative paths between `.md` files. Don’t hard-code the deployed site URL.
- Code references use the syntax documented in [Citing code](#citing-code-in-docs) below.
- Front matter is required: every page must start with a `---` block containing at least `title:`. `description:` and `order:` are recommended; SSG adapters will use them. CI enforces the `title:` requirement.
- Avoid headings that change frequently (release dates, version numbers in headings). Slugs change → links break.

### Running the docs lint locally

Two equivalent ways to validate the docs tree:

```bash
# Standalone, no composer install needed for the package
php packages/mksine/scripts/lint-docs.php
```

```bash
# Via the package’s composer script (requires composer install in packages/mksine/)
composer --working-dir=packages/mksine lint:docs
```

Both check that:

- Every Markdown file under `docs/` (excluding `archive/` and `internal/`) is in `_nav.yml` exactly once.
- Every `_nav.yml` entry exists on disk.
- Every page begins with YAML front matter containing a non-empty `title:` field.

The same checks live in `packages/mksine/tests/DocsNavTest.php` so they run as part of the Pest suite, and in `.github/workflows/docs-lint.yml` so they run on every PR that touches `docs/`.

### Style for technical docs

This documentation has an opinionated voice. Follow it.

- **Honest scope sections** are mandatory for any guide that introduces a feature. List what the feature does **not** do.
- **Honest limitations** sections cite real edge cases, not generic disclaimers.
- Use **bold** for the operative verb in a step ("**Run** `php artisan ...`"), not for emphasis-as-decoration.
- Show real code paths from the package using `startLine:endLine:filepath` references, not paraphrased pseudocode, when documenting framework behaviour.
- When a feature is configured but not implemented, say so explicitly. Don’t bury the gap.
- Don’t assume the reader knows Laravel internals beyond the basics; do assume they know PHP and basic OOP.

### Citing code in docs

For existing code in the repository:

````
```startLine:endLine:packages/mksine/path/to/File.php
// snippet
```
````

For example code or proposed code:

````
```php
// snippet
```
````

Keep snippets short. If a snippet exceeds ~30 lines, link to the source and excerpt the meaningful part.

## Architectural decisions

Significant architectural decisions live under `packages/mksine/docs/adr/` as numbered Markdown files. Follow the existing pattern:

```
NNN-short-title.md
```

Each ADR captures:

- **Context** — what problem you are addressing.
- **Decision** — what you chose.
- **Consequences** — what becomes harder, what becomes easier, what migration is required.

Add a new ADR before (or alongside) the implementation PR when:

- You are introducing a new public contract.
- You are changing the lifecycle of an existing one.
- You are adding a long-lived dependency on an upstream package.
- You are choosing between two viable approaches and the choice is non-obvious.

## Commits and changelog

- Conventional commits are not enforced; clear, scoped commit messages are expected.
- A line in `packages/mksine/CHANGELOG.md` per visible change. Group by `Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`.
- For breaking changes, the line must contain `**BREAKING:**` at the start.

## Reviewing checklist (for maintainers)

Before merging, confirm:

- [ ] Tests pass locally and in CI.
- [ ] Public surface impact assessed against [API stability](../reference/stability.md).
- [ ] If breaking, the `Upgrade guide` and `CHANGELOG` are updated.
- [ ] If introducing or changing a public contract, an ADR exists.
- [ ] `_nav.yml` updated when docs were added.
- [ ] No leakage of host-app-specific paths (`{plugin_root}` is the convention; no hardcoded `plugins/` outside the configured default).
- [ ] No client-specific plugin names or IDs in docs or code.

## What not to do

- Don't introduce silent fallbacks. If a config key is wrong, throw or log; don’t guess.
- Don't add features without an ADR for anything that has multiple viable designs.
- Don't expand the public surface "just in case". Every additional public method is a future deprecation.
- Don't add an admin toggle when a code-only switch is sufficient. Each toggle is a permission, a UI surface, a migration, and a future incident.
- Don't paper over a failing test with `markTestSkipped`. Fix it or delete it with a justification.

## See also

- [API stability](../reference/stability.md)
- [Versioning](versioning.md)
- [Upgrade guide](upgrade-guide.md)
- [SLO](slo.md)
- [Architecture decisions](../adr/) — start with the existing ADRs to understand the package's design intent.
