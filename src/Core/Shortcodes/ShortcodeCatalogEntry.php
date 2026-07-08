<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Shortcodes;

final readonly class ShortcodeCatalogEntry
{
    public function __construct(
        public string $tag,
        public string $label,
        public string $description = '',
        public string $example = '',
        public bool $selfClosing = true,
    ) {}

    /**
     * @return array{tag: string, label: string, description: string, example: string, selfClosing: bool}
     */
    public function toArray(): array
    {
        return [
            'tag' => $this->tag,
            'label' => $this->label,
            'description' => $this->description,
            'example' => $this->example,
            'selfClosing' => $this->selfClosing,
        ];
    }
}
