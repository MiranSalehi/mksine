<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater\Support;

use Miran\Mksine\Core\Updater\UpdateException;

/**
 * Diff-checker for core package composer.json update guard.
 *
 * Production servers do NOT have Composer available, so an update ZIP that
 * introduces or removes a dependency — or tightens/loosens a version
 * constraint — cannot be installed via this updater. The operator must
 * deploy the normal way.
 *
 * We compare the "require" and "require-dev" maps strictly:
 *   - Key added or removed    -> reject.
 *   - Key's value changed     -> reject.
 *
 * Everything else (scripts, extra, autoload additions) is fine.
 */
final class ComposerDependencyDiff
{
    /**
     * @return array{added: array<string,string>, removed: array<string,string>, changed: array<string,array{from:string,to:string}>}
     */
    public static function diff(array $current, array $next): array
    {
        $added = [];
        $removed = [];
        $changed = [];

        foreach (['require', 'require-dev'] as $key) {
            $currentMap = self::stringMap($current[$key] ?? []);
            $nextMap = self::stringMap($next[$key] ?? []);

            foreach ($nextMap as $pkg => $version) {
                if (! array_key_exists($pkg, $currentMap)) {
                    $added[$pkg] = $version;
                } elseif ($currentMap[$pkg] !== $version) {
                    $changed[$pkg] = ['from' => $currentMap[$pkg], 'to' => $version];
                }
            }

            foreach ($currentMap as $pkg => $version) {
                if (! array_key_exists($pkg, $nextMap)) {
                    $removed[$pkg] = $version;
                }
            }
        }

        return ['added' => $added, 'removed' => $removed, 'changed' => $changed];
    }

    public static function hasChanges(array $diff): bool
    {
        return ! (empty($diff['added']) && empty($diff['removed']) && empty($diff['changed']));
    }

    /**
     * Throw UpdateException::validation if the diff contains ANY dependency change.
     */
    public static function assertNoDependencyChanges(string $currentComposerJsonPath, string $newComposerJsonPath): void
    {
        $current = self::loadJson($currentComposerJsonPath);
        $next = self::loadJson($newComposerJsonPath);

        $diff = self::diff($current, $next);

        if (! self::hasChanges($diff)) {
            return;
        }

        $lines = ['Core update rejected: composer dependencies changed and this server has no composer access.'];
        foreach ($diff['added'] as $pkg => $ver) {
            $lines[] = "  + added:   {$pkg} ({$ver})";
        }
        foreach ($diff['removed'] as $pkg => $ver) {
            $lines[] = "  - removed: {$pkg} ({$ver})";
        }
        foreach ($diff['changed'] as $pkg => $vers) {
            $lines[] = "  ~ changed: {$pkg} ({$vers['from']} -> {$vers['to']})";
        }

        throw UpdateException::validation(implode("\n", $lines));
    }

    /**
     * @return array<string,string>
     */
    private static function stringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $pkg => $ver) {
            if (is_string($pkg) && is_string($ver)) {
                $out[$pkg] = $ver;
            }
        }

        return $out;
    }

    private static function loadJson(string $path): array
    {
        if (! is_file($path)) {
            throw UpdateException::validation("composer.json not found: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw UpdateException::validation("composer.json is invalid JSON: {$path}");
        }

        return $decoded;
    }
}
