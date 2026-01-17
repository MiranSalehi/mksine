<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Contracts;

/**
 * Contract for User models used by MKS CMS.
 *
 * Any User model used by MKS CMS must implement this interface.
 * This allows the package to work with any User model implementation
 * without hard-coded dependencies.
 */
interface MksUserInterface
{
    /**
     * Get the user's ID.
     */
    public function getId(): int | string;

    /**
     * Get the user's name.
     */
    public function getName(): string;

    /**
     * Get the user's email.
     */
    public function getEmail(): string;
}
