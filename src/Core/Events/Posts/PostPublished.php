<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Posts;

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Events\QueueableHookEventInterface;

/**
 * Fired after a post is created as published, or transitions into published.
 */
class PostPublished extends MksineEvent implements QueueableHookEventInterface
{
    public const string NAME = 'post.published';

    public function name(): string
    {
        return self::NAME;
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
            $payload['context'] ?? [],
        );
    }
}
