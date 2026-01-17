<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks\Exceptions;

/**
 * Exception thrown when a listener attempts to access a hook it does not have permission for.
 *
 * This exception is thrown when:
 * - A listener from a non-owner plugin attempts to listen to a 'private' hook
 * - A listener from a non-core/non-owner plugin attempts to listen to a 'system' hook
 */
final class HookVisibilityViolationException extends \RuntimeException
{
    public function __construct(
        string $hookName,
        string $listenerClass,
        string $listenerPluginId,
        string $hookOwnerPluginId,
        string $hookVisibility
    ) {
        $message = sprintf(
            'Hook visibility violation: Listener "%s" (plugin: "%s") attempted to access hook "%s" ' .
            '(owner: "%s", visibility: "%s"). Private hooks can only be accessed by the owner plugin. ' .
            'System hooks can only be accessed by core or the owner plugin.',
            $listenerClass,
            $listenerPluginId,
            $hookName,
            $hookOwnerPluginId,
            $hookVisibility
        );

        parent::__construct($message, 0, null);
    }
}
