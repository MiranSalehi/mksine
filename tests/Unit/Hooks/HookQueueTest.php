<?php

declare(strict_types=1);

/**
 * Unit tests for hook queue: async dispatcher is called with correct payload,
 * contract is enforced (allowAsync + no interface => throw), and config/disabled cases.
 *
 * Manual test (full flow with real queue):
 * 1. In PostCreatedListener set shouldQueue() to return true.
 * 2. config/mksine.php: hooks.queue.enabled => true, queue connection/name if needed.
 * 3. Run: php artisan queue:work --once (or queue:listen).
 * 4. In admin create a new Post; save.
 * 5. Check queue was processed: job runs PostCreatedListener::handle() in worker;
 *    check logs or add a side effect (e.g. log, DB write) in the listener to confirm.
 */
use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Events\QueueableHookEventInterface;
use Miran\Mksine\Core\Hooks\HookAsyncDispatcherInterface;
use Miran\Mksine\Core\Hooks\HookDispatcher;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\HookRegistry;
use Miran\Mksine\Core\Hooks\HookStateRepository;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

/** Fake that records dispatchAsync calls for testing */
final class FakeHookAsyncDispatcher implements HookAsyncDispatcherInterface
{
    public array $dispatched = [];

    public function dispatchAsync(string $listenerClass, string $eventClass, array $payload): void
    {
        $this->dispatched[] = [
            'listenerClass' => $listenerClass,
            'eventClass' => $eventClass,
            'payload' => $payload,
        ];
    }

    public function dispatchCount(): int
    {
        return count($this->dispatched);
    }
}

/**
 * HookManager subclass for tests: overrides shouldQueueListener so we don't depend on config().
 */
class HookManagerWithQueueFlag extends HookManager
{
    public static bool $queueEnabled = true;

    protected function shouldQueueListener(MksineEvent $event, string $listenerClass): bool
    {
        return self::$queueEnabled && $event->isAsyncAllowed();
    }
}

// Event that allows async and implements queue contract (for queue tests)
class QueueableTestEvent extends MksineEvent implements QueueableHookEventInterface
{
    public function name(): string
    {
        return 'queueable.test';
    }

    public function canBePrevented(): bool
    {
        return true;
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

// Event that allows async but does NOT implement interface (must throw)
class AsyncButNotQueueableEvent extends MksineEvent
{
    public function name(): string
    {
        return 'async.but.not.queueable';
    }

    public function canBePrevented(): bool
    {
        return true;
    }

    protected function allowAsync(): bool
    {
        return true;
    }
}

// Listener that opts into queue
class QueueableTestListener implements MksineListenerInterface
{
    public static bool $handledSync = false;

    public function handle(MksineEvent $event): void
    {
        self::$handledSync = true;
        $event->updateData('handled', true);
    }

    public function shouldHandle(MksineEvent $event): bool
    {
        return true;
    }

    public function shouldQueue(): bool
    {
        return true;
    }

    public function priority(): int
    {
        return 0;
    }
}

describe('HookManager queue integration', function () {
    beforeEach(function () {
        $this->registry = new HookRegistry;
        $this->stateRepository = new HookStateRepository;
        $this->dispatcher = new HookDispatcher;
        $this->fakeAsync = new FakeHookAsyncDispatcher;
        $this->manager = new HookManagerWithQueueFlag(
            $this->registry,
            $this->stateRepository,
            $this->dispatcher,
            $this->fakeAsync
        );
        HookManagerWithQueueFlag::$queueEnabled = true;
        QueueableTestListener::$handledSync = false;
    });

    it('calls async dispatcher with correct payload when listener shouldQueue and event allows async', function () {
        HookManagerWithQueueFlag::$queueEnabled = true;

        $this->manager->register('queueable.test', QueueableTestListener::class);

        $event = new QueueableTestEvent(
            ['id' => 1, 'title' => 'Test'],
            ['user_id' => 42]
        );

        $this->manager->dispatch($event);

        // Listener runs async, so not executed sync
        expect(QueueableTestListener::$handledSync)->toBeFalse();

        expect($this->fakeAsync->dispatchCount())->toBe(1);
        expect($this->fakeAsync->dispatched[0]['listenerClass'])->toBe(QueueableTestListener::class);
        expect($this->fakeAsync->dispatched[0]['eventClass'])->toBe(QueueableTestEvent::class);
        expect($this->fakeAsync->dispatched[0]['payload'])->toHaveKey('v', 1);
        expect($this->fakeAsync->dispatched[0]['payload']['data'])->toBe(['id' => 1, 'title' => 'Test']);
        expect($this->fakeAsync->dispatched[0]['payload']['context'])->toBe(['user_id' => 42]);
    });

    it('throws when event allowAsync is true but does not implement QueueableHookEventInterface', function () {
        HookManagerWithQueueFlag::$queueEnabled = true;

        $this->manager->register('async.but.not.queueable', QueueableTestListener::class);

        $event = new AsyncButNotQueueableEvent(['foo' => 'bar']);

        expect(fn () => $this->manager->dispatch($event))
            ->toThrow(LogicException::class, 'does not implement');
    });

    it('does not call async dispatcher when queue is disabled in config', function () {
        HookManagerWithQueueFlag::$queueEnabled = false;

        $this->manager->register('queueable.test', QueueableTestListener::class);

        $event = new QueueableTestEvent(['x' => 1], []);

        $this->manager->dispatch($event);

        expect($this->fakeAsync->dispatchCount())->toBe(0);
    });

    it('does not call async dispatcher when asyncDispatcher is null', function () {
        $managerWithoutAsync = new HookManager(
            $this->registry,
            $this->stateRepository,
            $this->dispatcher,
            null
        );
        $this->registry->register('queueable.test', QueueableTestListener::class);

        $event = new QueueableTestEvent(['x' => 1], []);

        $managerWithoutAsync->dispatch($event);

        expect($this->fakeAsync->dispatchCount())->toBe(0);
    });
});
