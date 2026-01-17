<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Canonical Hook Definition Contract.
 *
 * Represents the immutable definition of a hook, including:
 * - Hook name (event name or hook name)
 * - Owner plugin identifier
 * - Visibility level (public | private | system)
 *
 * IMMUTABILITY GUARANTEE:
 * - Visibility is part of hook DEFINITION, not runtime state
 * - Visibility CANNOT be overridden by config or database
 * - Default visibility MUST be 'private'
 *
 * This contract is enforced at registration time and cannot be modified.
 */
final class HookDefinition
{
    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_SYSTEM = 'system';

    /**
     * Plugin identifier for core/system hooks.
     */
    public const PLUGIN_CORE = 'core';

    /**
     * @param  string  $hookName  The hook name (event name for event hooks, hook name for form/table hooks)
     * @param  string  $ownerPluginId  The plugin identifier that owns this hook (use PLUGIN_CORE for core hooks)
     * @param  string  $visibility  Visibility level: 'public' | 'private' | 'system' (default: 'private')
     */
    public function __construct(
        private readonly string $hookName,
        private readonly string $ownerPluginId,
        private readonly string $visibility = self::VISIBILITY_PRIVATE
    ) {
        if (! in_array($visibility, [self::VISIBILITY_PUBLIC, self::VISIBILITY_PRIVATE, self::VISIBILITY_SYSTEM], true)) {
            throw new \InvalidArgumentException(
                "Invalid visibility level: {$visibility}. Must be one of: public, private, system"
            );
        }

        if (empty($ownerPluginId)) {
            throw new \InvalidArgumentException('Owner plugin identifier cannot be empty');
        }

        if (empty($hookName)) {
            throw new \InvalidArgumentException('Hook name cannot be empty');
        }
    }

    /**
     * Get the hook name.
     */
    public function hookName(): string
    {
        return $this->hookName;
    }

    /**
     * Get the owner plugin identifier.
     */
    public function ownerPluginId(): string
    {
        return $this->ownerPluginId;
    }

    /**
     * Get the visibility level.
     */
    public function visibility(): string
    {
        return $this->visibility;
    }

    /**
     * Check if this hook is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Check if this hook is private.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === self::VISIBILITY_PRIVATE;
    }

    /**
     * Check if this hook is system.
     */
    public function isSystem(): bool
    {
        return $this->visibility === self::VISIBILITY_SYSTEM;
    }

    /**
     * Check if this hook is owned by core.
     */
    public function isCoreOwned(): bool
    {
        return $this->ownerPluginId === self::PLUGIN_CORE;
    }
}
