<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use ReflectionObject;

/**
 * Walks a form schema tree and applies named before/after/replace slot hooks.
 *
 * @phpstan-type SlotEntry array{callback: callable, priority: int}
 * @phpstan-type SlotMap array<string, array<string, array<int, SlotEntry>>>
 */
final class FormSlotApplicator
{
    /**
     * @param  SlotMap  $slots  position => anchor => list of entries
     */
    public function apply(string $formName, Schema $schema, array $slots): Schema
    {
        if ($slots === []) {
            return $schema;
        }

        $components = $schema->getComponents(withActions: true, withHidden: true);

        return $schema->components(
            $this->processComponents($formName, $components, $slots)
        );
    }

    /**
     * @param  array<int, Component|Action|ActionGroup|mixed>  $components
     * @param  SlotMap  $slots
     * @return array<int, Component|Action|ActionGroup|mixed>
     */
    private function processComponents(string $formName, array $components, array $slots): array
    {
        $result = [];

        foreach ($components as $component) {
            if ($component instanceof Component) {
                $this->applyToChildren($formName, $component, $slots);
            }

            $anchor = $this->resolveAnchor($component);

            if ($anchor === null) {
                $result[] = $component;
                continue;
            }

            foreach ($this->collectInserts($formName, $slots, 'before', $anchor, $component) as $inserted) {
                $result[] = $inserted;
            }

            $replaced = $this->applyReplace($formName, $slots, $anchor, $component);

            if ($replaced !== null) {
                foreach ($replaced as $item) {
                    $result[] = $item;
                }
            }

            foreach ($this->collectInserts($formName, $slots, 'after', $anchor, $component) as $inserted) {
                $result[] = $inserted;
            }
        }

        return $result;
    }

    /**
     * Walk nested schema children without requiring a Livewire host.
     * Closure-based child schemas are skipped (cannot evaluate safely offline).
     *
     * @param  SlotMap  $slots
     */
    private function applyToChildren(string $formName, Component $component, array $slots): void
    {
        if (! method_exists($component, 'components')) {
            return;
        }

        $rawChildren = $this->rawDefaultChildComponents($component);

        if ($rawChildren === null || $rawChildren instanceof Closure) {
            return;
        }

        if ($rawChildren instanceof Schema) {
            $children = $rawChildren->getComponents(withActions: true, withHidden: true);
            $processed = $this->processComponents($formName, $children, $slots);
            $component->components($processed);

            return;
        }

        if (! is_array($rawChildren) || $rawChildren === []) {
            return;
        }

        $component->components(
            $this->processComponents($formName, array_values($rawChildren), $slots)
        );
    }

    /**
     * @return array<int, mixed>|Schema|Closure|null
     */
    private function rawDefaultChildComponents(Component $component): array|Schema|Closure|null
    {
        $reflection = new ReflectionObject($component);

        while ($reflection !== false && ! $reflection->hasProperty('childComponents')) {
            $reflection = $reflection->getParentClass();
        }

        if ($reflection === false) {
            return null;
        }

        $property = $reflection->getProperty('childComponents');
        $property->setAccessible(true);

        /** @var array<string, mixed> $map */
        $map = $property->getValue($component) ?? [];

        $default = $map['default'] ?? null;

        if ($default === null) {
            return null;
        }

        if ($default instanceof Closure || $default instanceof Schema || is_array($default)) {
            return $default;
        }

        return null;
    }

    /**
     * @param  SlotMap  $slots
     * @return list<Component>
     */
    private function collectInserts(
        string $formName,
        array $slots,
        string $position,
        string $anchor,
        mixed $original,
    ): array {
        $entries = $this->sortedEntries($slots[$position][$anchor] ?? []);

        if ($entries === []) {
            return [];
        }

        $inserted = [];

        foreach ($entries as $entry) {
            try {
                $value = ($entry['callback'])($original);
                foreach ($this->normalizeComponents($value) as $component) {
                    $inserted[] = $component;
                }
            } catch (\Throwable $e) {
                Log::error("FormSlotApplicator: Error in {$formName}.{$position}.{$anchor}: ".$e->getMessage());
            }
        }

        return $inserted;
    }

    /**
     * Last successful replace callback wins. null / [] hides the component.
     *
     * @param  SlotMap  $slots
     * @return list<Component>|null  null means hide
     */
    private function applyReplace(
        string $formName,
        array $slots,
        string $anchor,
        mixed $original,
    ): ?array {
        $entries = $this->sortedEntries($slots['replace'][$anchor] ?? []);

        if ($entries === []) {
            return $original instanceof Component || $original instanceof Action || $original instanceof ActionGroup
                ? [$original]
                : [$original];
        }

        $outcome = $original;
        $resolved = false;

        foreach ($entries as $entry) {
            try {
                $outcome = ($entry['callback'])($original);
                $resolved = true;
            } catch (\Throwable $e) {
                Log::error("FormSlotApplicator: Error in {$formName}.replace.{$anchor}: ".$e->getMessage());
            }
        }

        if (! $resolved) {
            return [$original];
        }

        if ($outcome === null) {
            return null;
        }

        $normalized = $this->normalizeComponents($outcome);

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  array<int, SlotEntry>  $entries
     * @return array<int, SlotEntry>
     */
    private function sortedEntries(array $entries): array
    {
        usort($entries, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $entries;
    }

    /**
     * @return list<Component>
     */
    private function normalizeComponents(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if ($value instanceof Component) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $components = [];

        foreach ($value as $item) {
            if ($item instanceof Component) {
                $components[] = $item;
            }
        }

        return $components;
    }

    private function resolveAnchor(mixed $component): ?string
    {
        if (! $component instanceof Component) {
            return null;
        }

        if ($component instanceof Section) {
            $key = $component->getKey(isAbsolute: false);

            return filled($key) ? "{$key}_section" : null;
        }

        $name = method_exists($component, 'getName') ? $component->getName() : null;
        $key = $component->getKey(isAbsolute: false);

        if (filled($key) && filled($name) && $key !== $name) {
            return (string) $key;
        }

        if (filled($name)) {
            return (string) $name;
        }

        return filled($key) ? (string) $key : null;
    }
}
