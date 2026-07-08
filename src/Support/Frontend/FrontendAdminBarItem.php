<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Frontend;

final readonly class FrontendAdminBarItem
{
    /**
     * @param  list<FrontendAdminBarItem>  $children
     */
    public function __construct(
        public string $label,
        public ?string $url = null,
        public bool $openInNewTab = false,
        public string $id = '',
        public int $priority = 10,
        public array $children = [],
    ) {}

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    public function isLink(): bool
    {
        return ! $this->hasChildren() && is_string($this->url) && $this->url !== '';
    }

    /**
     * @param  list<FrontendAdminBarItem>  $children
     */
    public function withChildren(array $children): self
    {
        return new self(
            label: $this->label,
            url: $this->url,
            openInNewTab: $this->openInNewTab,
            id: $this->id,
            priority: $this->priority,
            children: $children,
        );
    }
}
