<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Schemas\Components\Component;

/**
 * Discoverable listener for a named form slot (before / after / replace).
 *
 * hook_name in mks_hooks: "{formName}.{position}.{anchor}"
 */
interface FormSlotHookListenerInterface
{
    /**
     * Form identifier (e.g. "post.form").
     */
    public static function getFormName(): string;

    /**
     * Slot position: "before", "after", or "replace".
     */
    public static function getPosition(): string;

    /**
     * Stable anchor (field name, or "{sectionKey}_section").
     */
    public static function getAnchor(): string;

    /**
     * Lower numbers run first. For replace, later results win (last writer).
     */
    public static function getPriority(): int;

    /**
     * before/after: return Component|array of components to insert.
     * replace: receive the original component; return Component|array|null (null/[] = hide).
     *
     * @return Component|array<int, Component>|null
     */
    public static function handle(Component $original): Component|array|null;
}
