<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Categories;

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Events\QueueableHookEventInterface;

/**
 * Event fired after a category is updated.
 * This is an AFTER event, so it cannot be prevented.
 */
class CategoryUpdated extends MksineEvent implements QueueableHookEventInterface
{
    public function name(): string
    {
        return 'category.updated';
    }

    public function canBePrevented(): bool
    {
        return false;
    }

    protected function allowAsync(): bool
    {
        return true;
    }

    public function toQueuePayload(): array
    {
        return [
            'v' => 1,
            'data' => $this->allData(),
            'context' => $this->context(),
        ];
    }

    public static function fromQueuePayload(array $payload): static
    {
        return new static(
            $payload['data'] ?? [],
            $payload['context'] ?? []
        );
    }
}
